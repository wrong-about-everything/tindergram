<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

use Exception;

class NonExistent extends Mode
{
    public function value(): int
    {
        throw new Exception('This mode does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}