<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\ReadModel;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NMinutes;
use Meringue\Timeline\Point\Future;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class FromFeedbackInvitation implements MeetingRound
{
    private $feedbackInvitation;
    private $connection;
    private $cached;

    public function __construct(FeedbackInvitation $feedbackInvitation, OpenConnection $connection)
    {
        $this->feedbackInvitation = $feedbackInvitation;
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
        $meetingRound =
            (new Selecting(
                <<<q
select mr.*
from meeting_round mr
where bot_id = ? and start_date > ?
order by start_date desc
limit 1
q
                ,
                [$this->feedbackInvitation->value(), (new Future($this->startDateTime, new NMinutes(5)))->value()],
                $this->connection
            ))
                ->response();
        if (!$meetingRound->isSuccessful()) {
            return $meetingRound;
        }
        if (!isset($meetingRound->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($meetingRound->pure()->raw()[0]));
    }
}