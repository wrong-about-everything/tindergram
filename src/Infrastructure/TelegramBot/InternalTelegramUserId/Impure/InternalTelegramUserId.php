<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class InternalTelegramUserId
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(InternalTelegramUserId $userId): bool
    {
        return $this->value()->pure()->raw() === $userId->value()->pure()->raw();
    }
}