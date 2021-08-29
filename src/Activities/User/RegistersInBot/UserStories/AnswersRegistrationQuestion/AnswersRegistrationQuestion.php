<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion;

use TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\BotUser\RegisteredIfNoMoreQuestionsLeft;
use TG\Domain\BotUser\ReadModel\FromWriteModel;
use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationAnswerOption\Multiple\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Single\Impure\NextRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestionAnswer\WriteModel\Persistent;
use TG\Domain\TelegramBot\KeyboardButtons\KeyboardFromAnswerOptions;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\MessageToUser\DoNotReplyWithCustomMessagePushTheButtonInstead;
use TG\Infrastructure\TelegramBot\MessageToUser\Sorry;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

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
        if (!$currentlyAnsweredQuestion->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($currentlyAnsweredQuestion->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }
        if ($this->isInvalid($currentlyAnsweredQuestion, new UserReply($this->message))) {
            $this->validationError($currentlyAnsweredQuestion);
            return new Successful(new Emptie());
        }

        $savedAnswerValue = $this->savedAnswer($currentlyAnsweredQuestion)->value();
        if (!$savedAnswerValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($savedAnswerValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReply()->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers registration question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function currentlyAnsweredQuestion()
    {
        return
            new NextRegistrationQuestion(
                new FromParsedTelegramMessage($this->message),
                $this->connection
            );
    }

    private function validationError(RegistrationQuestion $registrationQuestion): ImpureValue
    {
        return
            (new DefaultWithKeyboard(
                new FromParsedTelegramMessage($this->message),
                new DoNotReplyWithCustomMessagePushTheButtonInstead(),
                new KeyboardFromAnswerOptions(
                    new FromImpure(
                        new FromRegistrationQuestion($registrationQuestion)
                    )
                ),
                $this->httpTransport
            ))
                ->value();
    }

    private function isInvalid(RegistrationQuestion $currentlyAnsweredQuestion, UserMessage $userReply): bool
    {
        return !(new FromRegistrationQuestion($currentlyAnsweredQuestion))->contain($userReply);
    }

    private function savedAnswer(RegistrationQuestion $currentlyAnsweredQuestion)
    {
        return
            new Persistent(
                new FromParsedTelegramMessage($this->message),
                new UserReply($this->message),
                $currentlyAnsweredQuestion,
                $this->connection
            );
    }

    private function sorry()
    {
        return
            new DefaultWithNoKeyboard(
                new FromParsedTelegramMessage($this->message),
                new Sorry(),
                $this->httpTransport
            );
    }


    private function nextReply(): SentReplyToUser
    {
        return
            new NextReplyToUser(
                new FromWriteModel(
                    new RegisteredIfNoMoreQuestionsLeft(
                        new FromParsedTelegramMessage($this->message),
                        $this->connection
                    ),
                    $this->connection
                ),
                $this->httpTransport,
                $this->connection
            );
    }
}