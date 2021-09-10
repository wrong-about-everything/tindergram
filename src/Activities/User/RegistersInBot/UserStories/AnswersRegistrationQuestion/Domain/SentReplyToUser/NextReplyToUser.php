<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\SentReplyToUser;

use TG\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionSentToUser;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\TelegramBot\MessageToUser\RegistrationCongratulations;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\FromBotUser as InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromImpure as PureInternalTelegramUserId;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithRemovedKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;

class NextReplyToUser implements MessageSentToUser
{
    private $botUser;
    private $httpTransport;
    private $connection;

    public function __construct(BotUser $botUser, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->botUser = $botUser;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return $this->botUser->value();
        }

        if ($this->userRegistered()) {
            return $this->congratulations();
        } else {
            return
                (new NextRegistrationQuestionSentToUser(
                    new PureInternalTelegramUserId(new InternalTelegramUserId($this->botUser)),
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function congratulations(): ImpureValue
    {
        return
            (new DefaultWithRemovedKeyboard(
                new PureInternalTelegramUserId(new InternalTelegramUserId($this->botUser)),
                new RegistrationCongratulations(),
                $this->httpTransport
            ))
                ->value();
    }

    private function userRegistered()
    {
        return
            (new FromBotUser($this->botUser))
                ->equals(
                    new FromPure(new Registered())
                );
    }
}