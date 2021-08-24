<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Method;

use TG\Infrastructure\Http\Request\Method;

class Delete extends Method
{
    public function value(): string
    {
        return 'DELETE';
    }
}