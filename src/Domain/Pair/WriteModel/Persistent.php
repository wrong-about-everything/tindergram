<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use Meringue\Timeline\Point\Now;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class Persistent implements Pair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
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
        return
            (new SingleMutating(
                <<<query
insert into viewed_pair (recipient_telegram_id, pair_telegram_id, viewed_at)
values (?, ?, ?)
on conflict (recipient_telegram_id, pair_telegram_id) do nothing
query
                ,
                [$this->recipientTelegramId->value(), $this->pairTelegramId->value(), (new Now())->value()],
                $this->connection
            ))
                ->response();
    }
}