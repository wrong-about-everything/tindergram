<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationAnswerOption\Multiple\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Single\Impure\NextRegistrationQuestion;
use TG\Domain\TelegramBot\KeyboardButtons\KeyboardFromAnswerOptions;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\SentReplyToUser;

class NextRegistrationQuestionReply implements SentReplyToUser
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