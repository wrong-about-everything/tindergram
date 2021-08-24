<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port;

use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port;
use Exception;

class DefaultPort implements Port
{
    public function value(): int
    {
        throw new Exception('Default port is used. If you want to specify a concrete port, use other classes instead.');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
