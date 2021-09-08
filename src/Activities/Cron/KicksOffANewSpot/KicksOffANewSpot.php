<?php

declare(strict_types=1);

namespace TG\Activities\Cron\KicksOffANewSpot;

use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\ViewedPair\RecipientInitiated;
use TG\Domain\ViewedPair\Sent;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\NonRetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;

class KicksOffANewSpot extends Existent
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

    // @todo: Как запускать это кроновое задание?
    // @todo: добавить логику обновления last_seen_at (Считать только по реакции на фото)
    // @todo: сделать кроновский запрос, который пингует тех, кто давно не заходил.
    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron kicks off a new spot scenario started'));

        $r =
            (new Selecting(
                <<<query
    select distinct on (recipient.telegram_id)
        recipient.telegram_id recipient_telegram_id, pair.telegram_id pair_telegram_id, pair.first_name pair_first_name
    from bot_user recipient
        join bot_user pair on recipient.preferred_gender = pair.gender and recipient.telegram_id != pair.telegram_id
    where recipient.status = ? and pair.status = ? and recipient.is_initiated = ?
    order by recipient.telegram_id, pair.seen_qty asc
    limit 50
query
                ,
                [(new Registered())->value(), (new Registered())->value(), 0],
                $this->connection
            ))
                ->response();
        if (!$r->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($r));
            return new NonRetryableServerError(new Emptie());
        }

        // @todo: предусмотреть что слать знак вопроса если у пользователя нет ни одной аватарки!
        array_map(
            function (array $row) {
                $recipientInitiated =
                    (new RecipientInitiated(
                        new Sent(
                            new FromInteger($row['recipient_telegram_id']),
                            new FromInteger($row['pair_telegram_id']),
                            $row['pair_first_name'],
                            $this->httpTransport
                        ),
                        new FromInteger($row['recipient_telegram_id']),
                        new FromInteger($row['pair_telegram_id']),
                        $this->connection
                    ))
                        ->value();
                if (!$recipientInitiated->isSuccessful()) {
                    $this->logs->receive(new FromNonSuccessfulImpureValue($recipientInitiated));
                }
                return $recipientInitiated;
            },
            $r->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Cron kicks off a new spot scenario finished'));

        return new Successful(new Emptie());
    }
}