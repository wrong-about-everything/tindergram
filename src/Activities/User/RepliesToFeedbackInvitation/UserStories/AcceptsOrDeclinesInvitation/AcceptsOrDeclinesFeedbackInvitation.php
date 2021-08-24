<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation;

use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\FeedbackInvitation\AcceptedOrDeclinedFeedbackInvitation;
use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply\NextReplyToUser;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use TG\Domain\FeedbackInvitation\ReadModel\LatestByFeedbackDate;
use TG\Domain\FeedbackInvitation\ReadModel\Refreshed;
use TG\Domain\FeedbackInvitation\WriteModel\FeedbackInvitation as WriteModelFeedbackInvitation;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString;
use TG\Infrastructure\Uuid\FromString as UuidFromString;

class AcceptsOrDeclinesFeedbackInvitation extends Existent
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
        $this->logs->receive(new InformationMessage('User replies to feedback invitation scenario started.'));

        $feedbackInvitation = $this->feedbackInvitation();
        if (!$feedbackInvitation->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($feedbackInvitation->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $acceptedOrDeclinedFeedbackInvitation = $this->acceptedOrDeclinedFeedbackInvitation($feedbackInvitation)->value();
        if (!$acceptedOrDeclinedFeedbackInvitation->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($acceptedOrDeclinedFeedbackInvitation));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser(new Refreshed($feedbackInvitation, $this->connection))->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User replies to feedback invitation scenario finished.'));

        return new Successful(new Emptie());
    }

    private function feedbackInvitation(): FeedbackInvitation
    {
        return
            new LatestByFeedbackDate(
                new FromParsedTelegramMessage($this->message),
                $this->botId(),
                $this->connection
            );
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString($this->botId));
    }

    private function acceptedOrDeclinedFeedbackInvitation(FeedbackInvitation $invitation): WriteModelFeedbackInvitation
    {
        return
            new AcceptedOrDeclinedFeedbackInvitation(
                $this->message,
                $invitation,
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