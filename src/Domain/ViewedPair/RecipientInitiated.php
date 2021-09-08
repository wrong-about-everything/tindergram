<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair;

use Meringue\Timeline\Point\Now;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RecipientInitiated implements ViewedPair
{
    private $original;
    private $recipientTelegramId;
    private $pairTelegramId;
    private $connection;

    public function __construct(ViewedPair $original, InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, OpenConnection $connection)
    {
        $this->original = $original;
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->original->value()->isSuccessful()) {
            return $this->original->value();
        }

        $updatedViewResponse =
            (new SingleMutating(
                <<<query
insert into view (recipient_telegram_id, pair_telegram_id, viewed_at)
values (?, ?, ?)
query
                ,
                [$this->recipientTelegramId->value(), $this->pairTelegramId->value(), (new Now())->value()],
                $this->connection
            ))
                ->response();
        if (!$updatedViewResponse->isSuccessful()) {
            return $updatedViewResponse;
        }

        $initiatedRecipient =
            (new SingleMutating(
                'update bot_user set is_initiated = ? where telegram_id = ?',
                [1, $this->recipientTelegramId->value()],
                $this->connection
            ))
                ->response();
        if (!$initiatedRecipient->isSuccessful()) {
            return $initiatedRecipient;
        }

        return
            (new SingleMutating(
                'update bot_user set seen_qty = seen_qty + 1 where telegram_id = ?',
                [$this->pairTelegramId->value()],
                $this->connection
            ))
                ->response();
    }
}