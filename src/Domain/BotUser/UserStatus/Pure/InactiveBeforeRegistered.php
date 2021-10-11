<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Pure;

class InactiveBeforeRegistered extends UserStatus
{
    public function value(): int
    {
        return 30;
    }

    public function exists(): bool
    {
        return true;
    }
}