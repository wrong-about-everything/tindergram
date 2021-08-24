<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply;

use TG\Activities\User\RepliesToRoundInvitation\Domain\Reply\NextRoundRegistrationQuestionReplyToUser;
use TG\Activities\User\RepliesToRoundInvitation\Domain\Reply\RoundRegistrationCongratulations;
use TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant\RegisteredIfNoMoreQuestionsLeft;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\MeetingRound\MeetingRoundId\Impure\FromInvitation as MeetingRoundIdFromInvitation;
use TG\Domain\MeetingRound\ReadModel\ById as MeetingRoundById;
use TG\Domain\Participant\ParticipantId\Impure\FromWriteModelParticipant;
use TG\Domain\Participant\ReadModel\ById;
use TG\Domain\Participant\Status\Impure\FromPure;
use TG\Domain\Participant\Status\Impure\FromReadModelParticipant;
use TG\Domain\Participant\Status\Pure\Registered;
use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundInvitation\InvitationId\Pure\FromImpure;
use TG\Domain\RoundInvitation\ReadModel\ById as InvitationById;
use TG\Domain\RoundInvitation\ReadModel\ByImpureId;
use TG\Domain\RoundInvitation\Status\Impure\FromInvitation;
use TG\Domain\RoundInvitation\Status\Impure\FromPure as ImpureInvitationStatusFromPure;
use TG\Domain\RoundInvitation\Status\Pure\Declined;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextReplyToUser implements SentReplyToUser
{
    private $invitationId;
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(InvitationId $invitationId, InternalTelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->invitationId->value()->isSuccessful()) {
            return $this->invitationId->value();
        }

        if ((new FromInvitation(new InvitationById(new FromImpure($this->invitationId), $this->connection)))->equals(new ImpureInvitationStatusFromPure(new Declined()))) {
            return $this->seeYouNextTime();
        } elseif ($this->participantRegisteredForARound()) {
            return $this->congratulations();
        } else {
            return
                (new NextRoundRegistrationQuestionReplyToUser(
                    $this->invitationId,
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function seeYouNextTime()
    {
        return
            (new InvitationDeclinedAndSeeYouNextTime(
                $this->telegramUserId,
                new ByBotId($this->botId, $this->connection),
                $this->httpTransport
            ))
                ->value();
    }

    private function congratulations()
    {
        return
            (new RoundRegistrationCongratulations(
                $this->telegramUserId,
                $this->botId,
                new MeetingRoundById(new MeetingRoundIdFromInvitation(new ByImpureId($this->invitationId, $this->connection)), $this->connection),
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function participantRegisteredForARound()
    {
        return
            (new FromReadModelParticipant(
                new ById(
                    new FromWriteModelParticipant(
                        new RegisteredIfNoMoreQuestionsLeft(
                            $this->invitationId,
                            $this->connection
                        )
                    ),
                    $this->connection
                )
            ))
                ->equals(
                    new FromPure(new Registered())
                );
    }
}