<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class IncrementedViewsQty implements BotUser
{
    private $telegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
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
                'update bot_user set seen_qty = seen_qty + 1 where telegram_id = ?',
                [$this->telegramUserId->value()],
                $this->connection
            ))
                ->response();
    }
}