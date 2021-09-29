<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

class Visible extends Mode
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