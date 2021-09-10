<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

use Exception;

class NonExistent extends Reaction
{
    function value(): int
    {
        throw new Exception('Reaction does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}