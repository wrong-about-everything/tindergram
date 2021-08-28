<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\Domain\Reply;

use TG\Activities\User\RegistersInBot\UserStories\Domain\BotUser\RegisteredIfNoMoreQuestionsLeft;
use TG\Domain\BotUser\ReadModel\FromWriteModel;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\RegistrationQuestion\Impure\NextRegistrationQuestion;
use TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Pure\FromImpure;
use TG\Domain\TelegramBot\KeyboardButtons\KeyboardFromAnswerOptions;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithMarkup;

class NextReplyToUser implements SentReplyToUser
{
    private $telegramUserId;
    private $httpTransport;
    private $connection;

    public function __construct(InternalTelegramUserId $telegramUserId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if ($this->userRegistered()) {
            return $this->congratulations();
        } else {
            return
                (new DefaultWithMarkup(
                    $this->telegramUserId,
                    (new NextRegistrationQuestion($this->telegramUserId, $this->connection))->value()->pure()->raw(),
                    new KeyboardFromAnswerOptions(
                        new FromImpure(
                            new FromRegistrationQuestion(
                                new NextRegistrationQuestion($this->telegramUserId, $this->connection)
                            )
                        )
                    ),
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function congratulations()
    {
        return
            (new RegistrationCongratulations(
                $this->telegramUserId,
                $this->httpTransport
            ))
                ->value();
    }

    private function userRegistered()
    {
        return
            (new FromBotUser(
                new FromWriteModel(
                    new RegisteredIfNoMoreQuestionsLeft(
                        $this->telegramUserId,
                        $this->connection
                    ),
                    $this->connection
                )
            ))
                ->equals(
                    new FromPure(new Registered())
                );
    }
}