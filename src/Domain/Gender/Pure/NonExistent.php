<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

use Exception;

class NonExistent extends Gender
{
    public function value(): int
    {
        throw new Exception('Gender does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}