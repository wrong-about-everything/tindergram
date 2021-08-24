<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials;

use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials;

class DefaultCredentials implements Credentials
{
    private $username;
    private $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

}
