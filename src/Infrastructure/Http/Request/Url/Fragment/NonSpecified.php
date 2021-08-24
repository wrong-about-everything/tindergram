<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Fragment;

use TG\Infrastructure\Http\Request\Url\Fragment;
use Exception;

class NonSpecified implements Fragment
{
    public function value(): string
    {
        throw new Exception('Fragment is not specified');
    }

    public function isSpecified(): bool
    {
        return false;
    }
}
