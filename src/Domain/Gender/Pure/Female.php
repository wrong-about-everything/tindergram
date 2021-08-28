<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

class Female extends Gender
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