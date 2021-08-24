<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Port
{
    public function value(): int;

    public function isSpecified(): bool;
}
