<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class SystemOrBusinessAnalyst extends Position
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