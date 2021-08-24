<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Header;

use Exception;
use TG\Infrastructure\Http\Response\Header;

class NonExistent extends Header
{
    public function value(): string
    {
        throw new Exception('Header does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}