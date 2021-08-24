<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Scheme;

use TG\Infrastructure\Http\Request\Url\Scheme;
use Exception;

class NonSpecified implements Scheme
{
    public function value(): string
    {
        throw new Exception('Scheme is not specified');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
