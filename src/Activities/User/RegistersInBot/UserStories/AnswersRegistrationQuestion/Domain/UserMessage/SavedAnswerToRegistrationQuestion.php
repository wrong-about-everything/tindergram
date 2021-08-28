<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\UserMessage;

use Exception;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Experience\ExperienceId\Pure\FromExperienceName;
use TG\Domain\Experience\ExperienceName\FromString as ExperienceName;
use TG\Domain\Position\PositionId\Pure\FromPositionName;
use TG\Domain\Position\PositionName\FromString;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionId\Impure\FromRegistrationQuestion as RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionId\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionId\Pure\RegistrationQuestionId as PureRegistrationQuestionId;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionType\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionType\Impure\FromRegistrationQuestion as ProfileRecordType;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionType\Pure\About;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestionType\Pure\Position;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\EmptyQuery;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\UserMessage\Impure\UserMessage;
use TG\Domain\TelegramBot\UserMessage\Pure\Skipped;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage as PureUserMessage;

class SavedAnswerToRegistrationQuestion implements UserMessage
{
    private $telegramUserId;
    private $botId;
    private $userMessage;
    private $question;
    private $connection;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, PureUserMessage $userMessage, RegistrationQuestion $question, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->userMessage = $userMessage;
        $this->question = $question;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $registrationQuestionId = new RegistrationQuestionId($this->question);
        if (!$registrationQuestionId->value()->isSuccessful()) {
            return $registrationQuestionId->value();
        }

        $updateProgressResponse = $this->persistenceResponse(new FromImpure($registrationQuestionId));
        if (!$updateProgressResponse->isSuccessful()) {
            return $updateProgressResponse;
        }

        return new Successful(new Present($this->userMessage->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->userMessage->exists()));
    }

    private function persistenceResponse(PureRegistrationQuestionId $registrationQuestionId)
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into user_registration_progress (registration_question_id, user_id)
select ?, id from "telegram_user" where telegram_id = ?
q
                        ,
                        [$registrationQuestionId->value(), $this->telegramUserId->value()],
                        $this->connection
                    ),
                    $this->updateBotUserQuery(),
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateBotUserQuery(): Query
    {
        if ((new ProfileRecordType($this->question))->equals(new FromPure(new Position()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set position = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromPositionName(new FromString($this->userMessage->value())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new Experience()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set experience = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromExperienceName(new ExperienceName($this->userMessage->value())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new About()))) {
            if ($this->userMessage->equals(new Skipped())) {
                return new EmptyQuery();
            }

            return
                new SingleMutating(
                    <<<q
update bot_user
set about = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [$this->userMessage->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        }

        throw new Exception(sprintf('Unknown user profile record type given: %s', (new ProfileRecordType($this->question))->value()->pure()->raw()));
    }
}