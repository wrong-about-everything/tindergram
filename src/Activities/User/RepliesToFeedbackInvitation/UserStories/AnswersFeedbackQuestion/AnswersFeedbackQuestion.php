<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion;

use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Participant\AnsweredFeedbackQuestion;
use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Reply\NextReplyToUser;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use TG\Domain\FeedbackInvitation\ReadModel\LatestByFeedbackDate;
use TG\Domain\FeedbackQuestion\FeedbackQuestion;
use TG\Domain\FeedbackQuestion\FirstNonAnsweredFeedbackQuestion;
use TG\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation as ParticipantIdFromFeedbackInvitation;
use TG\Domain\Participant\WriteModel\Participant;
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

class AnswersFeedbackQuestion extends Existent
{
    private $message;
    private $botId;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, string $botId, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User answers feedback question scenario started.'));

        $latestFeedbackInvitation = new LatestByFeedbackDate(new FromParsedTelegramMessage($this->message), $this->botId(), $this->connection);
        $currentlyAnsweredQuestion = $this->currentlyAnsweredQuestion($latestFeedbackInvitation);

        $participantValue = $this->participantAnsweredFeedbackQuestion($latestFeedbackInvitation, new UserReply($this->message), $currentlyAnsweredQuestion)->value();
        if (!$participantValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser($latestFeedbackInvitation)->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers feedback question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function currentlyAnsweredQuestion(FeedbackInvitation $latestFeedbackInvitation): FeedbackQuestion
    {
        return
            new FirstNonAnsweredFeedbackQuestion(
                new ParticipantIdFromFeedbackInvitation($latestFeedbackInvitation),
                $this->connection
            );
    }

    private function participantAnsweredFeedbackQuestion(FeedbackInvitation $feedbackInvitation, UserMessage $userReply, FeedbackQuestion $feedbackQuestion): Participant
    {
        return new AnsweredFeedbackQuestion($feedbackInvitation, $userReply, $feedbackQuestion, $this->connection);
    }

    private function botId(): BotId
    {
        return new FromUuid(new UuidFromString($this->botId));
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

    private function nextReplyToUser(FeedbackInvitation $feedbackInvitation)
    {
        return
            new NextReplyToUser(
                $feedbackInvitation,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}