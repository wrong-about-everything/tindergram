<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Method;

use TG\Infrastructure\Http\Request\Method;

class Options extends Method
{
    public function value(): string
    {
        return 'OPTIONS';
    }
}