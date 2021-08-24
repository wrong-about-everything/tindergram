<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection;

interface DatabaseName
{
    public function value(): string;

    public function isSpecified(): bool;
}
