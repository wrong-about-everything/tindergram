<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName;

use Exception;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName;

class NonSpecifiedDatabaseName implements DatabaseName
{
    public function value(): string
    {
        return '';
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
