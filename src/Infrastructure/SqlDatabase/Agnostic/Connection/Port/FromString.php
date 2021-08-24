<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port;

use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port;

class FromString implements Port
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): int
    {
        return (int) $this->value;
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
