<?php

declare(strict_types=1);

namespace TG\Activities\Cron\AsksForFeedback;

use Meringue\Timeline\Point\Now;
use Ramsey\Uuid\Uuid;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\ById;
use TG\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString;
use TG\Domain\FeedbackInvitation\Status\Pure\ErrorDuringSending;
use TG\Domain\FeedbackInvitation\Status\Pure\Generated;
use TG\Domain\FeedbackInvitation\Status\Pure\Sent as SentStatus;
use TG\Domain\FeedbackInvitation\WriteModel\WithPause;
use TG\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use TG\Domain\MeetingRound\ReadModel\ByClosestFeedbackDateTime;
use TG\Domain\MeetingRound\ReadModel\MeetingRound;
use TG\Domain\FeedbackInvitation\WriteModel\Sent;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\UserStory\Response\RetryableServerError;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class AsksForFeedback extends Existent
{
    private $botId;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron asks for feedback scenario started'));

        $meetingRoundWithLatestFeedbackDate = new ByClosestFeedbackDateTime($this->botId, new Now(), $this->connection);
        if (!$meetingRoundWithLatestFeedbackDate->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($meetingRoundWithLatestFeedbackDate->value()));
            return new RetryableServerError(new Emptie());
        }
        if (empty($this->feedbackInvitationsForRound($meetingRoundWithLatestFeedbackDate))) {
            $response = $this->generateInvitation($meetingRoundWithLatestFeedbackDate);
            if (!$response->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($response));
                return new RetryableServerError(new Emptie());
            }
        }

        array_map(
            function (array $invitationRow) {
                (new WithPause(
                    new Sent(
                        new FromString($invitationRow['id']),
                        new FromInteger($invitationRow['telegram_user_id']),
                        new ById($this->botId, $this->connection),
                        $this->transport,
                        $this->connection,
                        $this->logs
                    ),
                    100000
                ))
                    ->value();
            },
            $this->notSentFeedbackInvitationsForRound($meetingRoundWithLatestFeedbackDate)
        );

        $this->logs->receive(new InformationMessage('Cron asks for feedback scenario finished'));

        return new Successful(new Emptie());
    }

    private function feedbackInvitationsForRound(MeetingRound $round)
    {
        return
            (new Selecting(
                <<<q
select fi.id, tu.telegram_id as telegram_user_id
from feedback_invitation fi
    join meeting_round_participant mrp on fi.participant_id = mrp.id
    join telegram_user tu on tu.id = mrp.user_id
where mrp.meeting_round_id = ?
q
                ,
                [(new FromMeetingRound($round))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }

    private function notSentFeedbackInvitationsForRound(MeetingRound $round)
    {
        return
            (new Selecting(
                <<<q
select fi.id, tu.telegram_id as telegram_user_id
from feedback_invitation fi
    join meeting_round_participant mrp on fi.participant_id = mrp.id
    join telegram_user tu on tu.id = mrp.user_id
where mrp.meeting_round_id = ? and fi.status in (?)
limit 50
q
                ,
                [(new FromMeetingRound($round))->value()->pure()->raw(), [(new Generated())->value(), (new ErrorDuringSending())->value()]],
                $this->connection
            ))
                ->response()->pure()->raw();
    }

    private function generateInvitation(MeetingRound $meetingRoundWithLatestFeedbackDate)
    {
        return
            (new SingleMutatingQueryWithMultipleValueSets(
                <<<q
insert into feedback_invitation (id, participant_id, status)
values (?, ?, ?)
q
                ,
                array_map(
                    function (array $participantId) use ($meetingRoundWithLatestFeedbackDate) {
                        return [Uuid::uuid4()->toString(), $participantId['id'], (new Generated())->value()];
                    },
                    $this->participantsThatHadAPairInAMeetingRound($meetingRoundWithLatestFeedbackDate)
                ),
                $this->connection
            ))
                ->response();
    }

    private function participantsThatHadAPairInAMeetingRound(MeetingRound $meetingRoundWithLatestFeedbackDate)
    {
        return
            (new Selecting(
                <<<q
select mrp.id
from meeting_round_participant mrp
where mrp.meeting_round_id = ? and exists(select * from meeting_round_pair where participant_id = mrp.id)
q
                ,
                [(new FromMeetingRound($meetingRoundWithLatestFeedbackDate))->value()->pure()->raw()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}