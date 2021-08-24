<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class UserStatus
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(UserStatus $status): bool
    {
        return $this->value()->pure()->raw() === $status->value()->pure()->raw();
    }
}