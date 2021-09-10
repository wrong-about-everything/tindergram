<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

class Dislike extends Reaction
{
    function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}