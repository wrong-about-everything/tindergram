<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\BotUser;

use TG\Domain\BotUser\UserId\FromReadModelBotUser;
use TG\Domain\BotUser\WriteModel\BotUser;
use TG\Domain\BotUser\ReadModel\BotUser as ReadModelBotUser;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\RegistrationQuestion\Multiple\Impure\Unanswered;
use TG\Domain\RegistrationQuestion\Multiple\Pure\All;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RegisteredIfNoMoreQuestionsLeft implements BotUser
{
    private $internalTelegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->internalTelegramUserId = $telegramUserId;
        $this->connection = $connection;

        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $botUser = new ByInternalTelegramUserId($this->internalTelegramUserId, $this->connection);

        $unansweredQuestions = $this->unansweredQuestions($botUser);
        if (!$unansweredQuestions->isSuccessful()) {
            return $unansweredQuestions;
        }
        if (empty($unansweredQuestions->pure()->raw())) {
            $registerResponse = $this->register();
            if (!$registerResponse->isSuccessful()) {
                return $registerResponse;
            }
        }

        return
            new Successful(
                new Present(
                    (new FromReadModelBotUser($botUser))
                        ->value()
                )
            );
    }

    private function unansweredQuestions(ReadModelBotUser $botUser): ImpureValue
    {
        return
            (new Unanswered(
                new All(),
                $botUser
            ))
                ->value();
    }

    private function register(): ImpureValue
    {
        return
            (new SingleMutating(
                <<<q
update bot_user
set status = ?
where telegram_id = ?
q

                ,
                [(new Registered())->value(), $this->internalTelegramUserId->value()],
                $this->connection
            ))
                ->response();
    }
}