<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Url\Host;

use TG\Infrastructure\Http\Request\Url\Host;

class Localhost implements Host
{
    public function value(): string
    {
        return 'localhost';
    }
}
