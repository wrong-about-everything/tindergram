<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Pure;

class InactiveAfterRegistered extends UserStatus
{
    public function value(): int
    {
        return 20;
    }

    public function exists(): bool
    {
        return true;
    }
}