<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Scheme;

use TG\Infrastructure\Http\Request\Url\Scheme;

class Http implements Scheme
{
    public function value(): string
    {
        return 'http://';
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
