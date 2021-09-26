<?php

declare(strict_types=1);

namespace TG\Activities\User\SeesNextPair;

use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\NonRetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;

class SeesNextPair extends Existent
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

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Uses sees next pair scenario started'));

        /**
         * 2 (?) Если текущий пользователь зарегался в течение последний X (его надо определить!!) дней, он в инкубации:
         *    показывать ему только тем, кто сами зарегались в течение последний трёх дней.
         *    Это для того, чтоб он видел знакомые лица.
         *
         */

        // Как выбрать следующую пару? Вот какие критерии надо учесть:
        //  - не показывать пару второй раз. (+)
        //  - в первую очередь я вижу новых людей. Они только что присоединились и их надо завлечь лайками. (+)
        //  - в первую очередь я вижу активных людей (?) (А как завлекать отвалившихся? Слать раз в день топовые профили? Или всё равно показывать их профили активным пользователям и опять-таки по крону слать профили тех, кто их лайкнул и намекать что эти чуваки их лайкнули?)
        $r =
            (new Selecting(
                <<<query
    select distinct on (recipient.telegram_id)
        recipient.telegram_id recipient_telegram_id, pair.telegram_id pair_telegram_id
    from bot_user recipient
        join bot_user pair on recipient.preferred_gender = pair.gender and recipient.telegram_id != pair.telegram_id
        left join viewed_pair on viewed_pair.recipient_telegram_id = recipient.telegram_id and viewed_pair.pair_telegram_id = pair.telegram_id
    where
        recipient.status = ? and pair.status = ?
            and
        viewed_pair.recipient_telegram_id is null
    order by recipient.telegram_id, pair.seen_qty asc, pair.last_seen_at desc
    limit 50
query
                ,
                [(new Registered())->value(), (new Registered())->value()],
                $this->connection
            ))
                ->response();
        if (!$r->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($r));
            return new NonRetryableServerError(new Emptie());
        }

        $this->logs->receive(new InformationMessage('Uses sees next pair scenario finished'));

        return new Successful(new Emptie());
    }
}