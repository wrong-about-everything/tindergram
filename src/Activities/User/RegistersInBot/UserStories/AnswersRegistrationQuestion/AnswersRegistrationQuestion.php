<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion;

use TG\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
use TG\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion as AnswerOptionsFromRegistrationQuestion;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Experience\ExperienceName\FromString;
use TG\Domain\Position\PositionName\FromString as PositionNameFromString;
use TG\Domain\RegistrationQuestion\NextRegistrationQuestion;
use TG\Activities\User\RegistersInBot\UserStories\Domain\Reply\NextReplyToUserToUser;
use TG\Domain\RegistrationQuestion\RegistrationQuestion;
use TG\Domain\SentReplyToUser\ValidationError;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromPure;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString as UuidFromString;
use TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\UserMessage\SavedAnswerToRegistrationQuestion;

class AnswersRegistrationQuestion extends Existent
{
    private $message;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User answers registration question scenario started.'));

        $currentlyAnsweredQuestion = $this->currentlyAnsweredQuestion();
        if ($this->isInvalid($currentlyAnsweredQuestion, new UserReply($this->message))) {
            $this->validationError(new AnswerOptionsFromRegistrationQuestion($currentlyAnsweredQuestion, $this->botId(), $this->connection))->value();
            return new Successful(new Emptie());
        }

        $savedAnswerValue = $this->savedAnswer($currentlyAnsweredQuestion)->value();
        if (!$savedAnswerValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($savedAnswerValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser()->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers registration question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function botId()
    {
        return new FromUuid(new UuidFromString($this->botId));
    }

    private function currentlyAnsweredQuestion()
    {
        return
            new NextRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection
            );
    }

    private function validationError(ReplyOptions $answerOptions)
    {
        return
            new ValidationError(
                $answerOptions,
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function isInvalid(RegistrationQuestion $currentlyAnsweredQuestion, UserMessage $userReply): bool
    {
        return
            (
                (new FromRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new Position()))
                    &&
                !(new PositionNameFromString($userReply->value()))->exists()
            )
                ||
            (
                (new FromRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new Experience()))
                    &&
                !(new FromString($userReply->value()))->exists()
            );
    }

    private function savedAnswer(RegistrationQuestion $currentlyAnsweredQuestion)
    {
        return
            new SavedAnswerToRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                new UserReply($this->message),
                $currentlyAnsweredQuestion,
                $this->connection
            );
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function nextReplyToUser()
    {
        return
            new NextReplyToUserToUser(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}