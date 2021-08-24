<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation;

use TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant\RepliedToInvitation;
use TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply\NextReplyToUser;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Participant\WriteModel\Participant;
use TG\Domain\RoundInvitation\InvitationId\Impure\FromInvitation as InvitationIdFromInvitation;
use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundInvitation\ReadModel\Invitation;
use TG\Domain\RoundInvitation\ReadModel\LatestInvitation;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage as UserIdFromParsedTelegramMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString as UuidFromString;

class AcceptsOrDeclinesInvitation extends Existent
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
        $this->logs->receive(new InformationMessage('User replies to round invitation scenario started.'));

        $invitation = $this->invitation();
        if (!$invitation->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($invitation->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $participantRepliedToInvitationValue = $this->participantRepliedToInvitation($invitation)->value();
        if (!$participantRepliedToInvitationValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantRepliedToInvitationValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser(new InvitationIdFromInvitation($invitation))->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User replies to round invitation scenario finished.'));

        return new Successful(new Emptie());
    }

    private function invitation(): Invitation
    {
        return
            new LatestInvitation(
                new UserIdFromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection
            );
    }

    private function participantRepliedToInvitation(Invitation $invitation): Participant
    {
        return
            new RepliedToInvitation(
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

    private function nextReplyToUser(InvitationId $invitationId)
    {
        return
            new NextReplyToUser(
                $invitationId,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}