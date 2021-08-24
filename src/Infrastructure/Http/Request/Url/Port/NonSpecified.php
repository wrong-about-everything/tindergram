<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Port;

use TG\Infrastructure\Http\Request\Url\Port;
use Exception;

class NonSpecified implements Port
{
    public function value(): int
    {
        throw new Exception('Port is not specified');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
