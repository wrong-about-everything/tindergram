<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

abstract class InternalTelegramUserId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(InternalTelegramUserId $userId): bool
    {
        return $this->value() === $userId->value();
    }
}