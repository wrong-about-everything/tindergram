<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationAnswerOption\Multiple\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Single\Impure\NextRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Impure\FromRegistrationQuestion as RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\AreYouReadyToRegisterId;
use TG\Domain\TelegramBot\KeyboardButtons\KeyboardFromAnswerOptions;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\Emptie;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstFive;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\NonDeleted;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;

class NextRegistrationQuestionSentToUser implements SentReplyToUser
{
    private $internalTelegramUserId;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->internalTelegramUserId = $internalTelegramUserId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $nextRegistrationQuestion = new NextRegistrationQuestion($this->internalTelegramUserId, $this->connection);
        if (!$nextRegistrationQuestion->value()->isSuccessful()) {
            return $nextRegistrationQuestion->value();
        }

        if ((new RegistrationQuestionId($nextRegistrationQuestion))->equals(new FromPure(new AreYouReadyToRegisterId()))) {
            $v =
                (new SentToUser(
                    new FromString($nextRegistrationQuestion->value()->pure()->raw()),
                    new FirstFive(
                        new NonDeleted(
                            $this->internalTelegramUserId,
                            new FromTelegram(
                                $this->internalTelegramUserId,
                                $this->httpTransport
                            ),
                            $this->httpTransport
                        )
                    ),
                    $this->internalTelegramUserId,
                    $this->httpTransport
                ))
                    ->value();
            if (!$v->isSuccessful()) {
                return $v;
            }

            return
                (new DefaultWithKeyboard(
                    $this->internalTelegramUserId,
                    new Emptie(),
                    new KeyboardFromAnswerOptions(
                        new FromImpure(
                            new FromRegistrationQuestion(
                                $nextRegistrationQuestion
                            )
                        )
                    ),
                    $this->httpTransport
                ))
                    ->value();
        } else {
            return
                (new DefaultWithKeyboard(
                    $this->internalTelegramUserId,
                    new FromString($nextRegistrationQuestion->value()->pure()->raw()),
                    new KeyboardFromAnswerOptions(
                        new FromImpure(
                            new FromRegistrationQuestion(
                                new NextRegistrationQuestion($this->internalTelegramUserId, $this->connection)
                            )
                        )
                    ),
                    $this->httpTransport
                ))
                    ->value();
        }
    }
}