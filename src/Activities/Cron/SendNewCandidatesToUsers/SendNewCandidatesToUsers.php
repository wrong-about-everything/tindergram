<?php

declare(strict_types=1);

namespace TG\Activities\Cron\SendNewCandidatesToUsers;

use Meringue\ISO8601DateTime;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class SendNewCandidatesToUsers extends Existent
{
    private $now;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(ISO8601DateTime $now, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->now = $now;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Send new candidates to users scenario started'));

        // 1. get the list of users who've made it to the end yesterday or earlier
        $usersWhoHaveMadeItToTheEndYesterday = $this->usersWhoHaveMadeItToTheEnd();
        // 2. iterate over them and send new pair, if present

        $this->logs->receive(new InformationMessage('Send new candidates to users scenario finished'));

        return new Successful(new Emptie());
    }

    private function usersWhoHaveMadeItToTheEnd(): array
    {
        return
            (new Selecting(
                <<<qqqqqqqqqq
select *
from (
    select
        bu.telegram_id,
        first_value(vp.viewed_at) over viewed_pairs as latest_viewed_at,
        first_value(vp.reaction) over viewed_pairs as latest_reaction
    from bot_user bu
        join viewed_pair vp on bu.telegram_id = vp.recipient_telegram_id
    window viewed_pairs as (partition by vp.recipient_telegram_id order by viewed_at desc)
) as latest_recipients_views
where latest_recipients_views.latest_viewed_at < ? and latest_recipients_views.reaction is not null
qqqqqqqqqq
                ,
                [/* today's midnight */],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}