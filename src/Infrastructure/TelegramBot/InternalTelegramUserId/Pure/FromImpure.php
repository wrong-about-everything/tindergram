<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\InternalTelegramUserId as ImpureInternalTelegramUserId;

class FromImpure extends InternalTelegramUserId
{
    private $impureInternalTelegramUserId;

    public function __construct(ImpureInternalTelegramUserId $impureInternalTelegramUserId)
    {
        $this->impureInternalTelegramUserId = $impureInternalTelegramUserId;
    }

    public function value(): int
    {
        return $this->impureInternalTelegramUserId->value()->pure()->raw();
    }

    public function exists(): bool
    {
        return $this->impureInternalTelegramUserId->exists()->pure()->raw();
    }
}