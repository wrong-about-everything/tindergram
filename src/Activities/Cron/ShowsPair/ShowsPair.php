<?php

declare(strict_types=1);

namespace TG\Activities\Cron\ShowsPair;

use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;

class ShowsPair extends Existent
{
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    // @todo: Как запускать это кроновео задание?
    // @todo: добавить last_seen_at
    // @todo: добавить registered_at
    // @todo: сделать кроновский запрос, который пингует тех, кто давно не заходил.
    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron shows pair scenario started'));

        // Послать одну пару каждому: парню -- девушку, девушке -- парня.
        // Как выбирать пары? Вот какие критерии надо учесть:
        //  - если я кому-то понравился, я должен видеть этих людей, пока не надоест листать. Вероятно, в первой сотне;
        //  - не показывать пару второй раз.
        //  - в первую очередь я вижу новых людей. Они только что присоединились и их надо завлечь лайками.
        //  - в первую очередь я вижу активных людей.
        // Записать, что тому-то послали того-то.
        // После этого новые пары будут слать после того, как пользователь оценит предыдущую пару.

        new Selecting(
            <<<query
select *
from bot_user recipient join bot_user pair on 
where gender = ?
order by seen_qty asc last_seen_at desc
where last_seen_at > now() - interval '1 day'
query
            ,
            [],
            $this->connection
        );

        $this->logs->receive(new InformationMessage('Cron shows pair scenario finished'));
    }
}