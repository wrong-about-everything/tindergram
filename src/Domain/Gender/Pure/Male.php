<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

class Male extends Gender
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