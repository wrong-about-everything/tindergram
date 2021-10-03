<?php

declare(strict_types=1);

namespace TG\Infrastructure\ABTesting\Pure;

use Exception;

class NonExistent extends VariantId
{
    public function value(): int
    {
        throw new Exception('This variant does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}