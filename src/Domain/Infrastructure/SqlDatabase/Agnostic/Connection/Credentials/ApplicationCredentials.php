<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials;

use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials;

class ApplicationCredentials implements Credentials
{
    public function username(): string
    {
        return getenv('DB_USER');
    }

    public function password(): string
    {
        return getenv('DB_PASS');
    }
}
