<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection\Host;

use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Host;
use Exception;

class FromString implements Host
{
    private $host;

    public function __construct(string $host)
    {
        if ($host === '') {
            throw new Exception('Please specify host explicitly');
        }

        $this->host = $host;
    }

    public function value(): string
    {
        return $this->host;
    }
}
