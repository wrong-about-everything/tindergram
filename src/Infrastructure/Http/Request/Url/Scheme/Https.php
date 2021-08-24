<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Scheme;

use TG\Infrastructure\Http\Request\Url\Scheme;

class Https implements Scheme
{
    public function value(): string
    {
        return 'https://';
    }

    public function isSpecified(): bool
    {
        return true;
    }
}
