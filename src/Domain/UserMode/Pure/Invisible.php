<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

class Invisible extends Mode
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}