<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithRemovedKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;
use TG\Infrastructure\TelegramBot\SentReplyToUser\Nothing;

class FirstSentPairAfterRegistrationIfExistsNoCandidatesYetOtherwise implements Pair
{
    private $candidate;
    private $recipientTelegramId;
    private $transport;
    private $connection;

    public function __construct(BotUser $candidate, InternalTelegramUserId $recipientTelegramId, HttpTransport $transport, OpenConnection $connection)
    {
        $this->candidate = $candidate;
        $this->recipientTelegramId = $recipientTelegramId;
        $this->transport = $transport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->candidate->value()->pure()->isPresent()) {
            return $this->congratulationsButWeHaveNoCandidatesForYouAtTheMoment()->value();
        }

        $congratulationsResponse = $this->congratulationsAndHereIsYourFirstPair()->value();
        if (!$congratulationsResponse->isSuccessful()) {
            return $congratulationsResponse;
        }

        return
            (new SentIfExists(
                $this->candidate,
                $this->recipientTelegramId,
                new Nothing(),
                $this->transport,
                $this->connection
            ))
                ->value();
    }

    private function congratulationsButWeHaveNoCandidatesForYouAtTheMoment(): MessageSentToUser
    {
        return
            new DefaultWithRemovedKeyboard(
                $this->recipientTelegramId,
                new FromString('Поздравляем, вы зарегистрировались! Правда, пока у нас нет кандидатов, удовлетворяющих вашим критериям. Но как только появятся, сразу пришлём!'),
                $this->transport
            );
    }

    private function congratulationsAndHereIsYourFirstPair(): MessageSentToUser
    {
        return
            new DefaultWithRemovedKeyboard(
                $this->recipientTelegramId,
                new FromString('Поздравляем, вы зарегистрировались! Вот ваша первая пара:'),
                $this->transport
            );
    }
}