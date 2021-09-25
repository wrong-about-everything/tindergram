<?php

declare(strict_types=1);

namespace TG\Activities\User\RatesAPair\Domain;

use TG\Domain\Pair\WriteModel\Pair;
use TG\Domain\Pair\WriteModel\SentPair;
use TG\Domain\TelegramBot\MessageToUser\ThatsAllForNow;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;

class NextMessage implements MessageSentToUser
{
    private $recipientTelegramId;
    private $transport;
    private $connection;

    public function __construct(InternalTelegramUserId $recipientTelegramId, HttpTransport $transport, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->transport = $transport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $nextPair = $this->nextPair();
        if (!$nextPair->isSuccessful()) {
            return $nextPair;
        }
        if (!isset($nextPair->pure()->raw()[0])) {
            return $this->thatsAllForNow()->value();
        }

        return $this->sentPair($nextPair->pure()->raw()[0])->value();
    }

    private function nextPair(): ImpureValue
    {
        // @todo: если я кому-то понравился, я должен видеть этих людей, пока не надоест листать. Вероятно, в первой сотне; (в сценарии показа пары после оценки предыдущей)

        // Если будет медленно, попробовать
        //  - distinct on (recipient.telegram_id)
        //  - min (seen_qty)
        return
            (new Selecting(
                <<<query
    select pair.telegram_id pair_telegram_id, pair.first_name pair_first_name
    from bot_user recipient
        join bot_user pair on recipient.preferred_gender = pair.gender and recipient.gender = pair.preferred_gender and recipient.telegram_id != pair.telegram_id
        left join viewed_pair on viewed_pair.recipient_telegram_id = recipient.telegram_id and viewed_pair.pair_telegram_id = pair.telegram_id
    where
        recipient.telegram_id = ?
            and
        viewed_pair.recipient_telegram_id is null
            and
        pair.is_initiated = ?
    order by pair.seen_qty asc
    limit 1
query
                ,
                [$this->recipientTelegramId->value(), 1],
                $this->connection
            ))
                ->response();
    }

    private function sentPair(array $nextPair): Pair
    {
        return
            new SentPair(
                $this->recipientTelegramId,
                new FromInteger($nextPair['pair_telegram_id']),
                $nextPair['pair_first_name'],
                $this->transport,
                $this->connection
            );
    }

    private function thatsAllForNow(): MessageSentToUser
    {
        return new DefaultWithNoKeyboard($this->recipientTelegramId, new ThatsAllForNow(), $this->transport);
    }
}