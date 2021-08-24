<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Pure;

class RegistrationIsInProgress extends UserStatus
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}