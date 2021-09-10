<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

class Like extends Reaction
{
    function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}