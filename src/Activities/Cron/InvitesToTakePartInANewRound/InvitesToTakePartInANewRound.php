<?php

declare(strict_types=1);

namespace TG\Activities\Cron\InvitesToTakePartInANewRound;

use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\ById;
use TG\Domain\RoundInvitation\InvitationId\Pure\FromUuid;
use TG\Infrastructure\Uuid\FromString as Uuid;
use TG\Domain\RoundInvitation\WriteModel\Sent;
use TG\Domain\RoundInvitation\Status\Pure\Sent as SentStatus;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Domain\RoundInvitation\WriteModel\WithPause;

class InvitesToTakePartInANewRound extends Existent
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
        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario started'));

        array_map(
            function (array $meetingRoundInvitation) {
                return
                    (new WithPause(
                        new Sent(
                            new FromUuid(new Uuid($meetingRoundInvitation['id'])),
                            new FromInteger($meetingRoundInvitation['telegram_id']),
                            new ById($this->botId, $this->connection),
                            $this->transport,
                            $this->connection,
                            $this->logs
                        ),
                        100000
                    ))
                        ->value();
            },
            (new Selecting(
                <<<q
select mri.id, u.telegram_id, b.token, b.name
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join "telegram_user" u on mri.user_id = u.id
    join bot b on b.id = mr.bot_id
    left join meeting_round_participant mrp on mrp.user_id = u.id and mrp.meeting_round_id = mr.id
where mr.bot_id = ? and mri.status != ? and mr.invitation_date <= now() + interval '1 minute' and mrp.id is null
limit 100
q
                ,
                [$this->botId->value(), (new SentStatus())->value()],
                $this->connection
            ))
                ->response()->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Cron invites to attend in a new round scenario finished'));

        return new Successful(new Emptie());
    }
}