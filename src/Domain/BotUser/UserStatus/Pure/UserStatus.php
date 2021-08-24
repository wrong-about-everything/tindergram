<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Pure;

abstract class UserStatus
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(UserStatus $status): bool
    {
        return $this->value() === $status->value();
    }
}