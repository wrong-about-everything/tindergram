<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection;

use PDO;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\NonSpecifiedDatabaseName;
use TG\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;

class ConnectionToSystemDatabase implements OpenConnection
{
    private $port;
    private $host;
    private $credentials;
    private $pdo;

    public function __construct(Port $port, Host $host, Credentials $credentials)
    {
        $this->port = $port;
        $this->host = $host;
        $this->credentials = $credentials;
        $this->pdo = null;
    }

    public function value(): PDO
    {
        if (is_null($this->pdo)) {
            $this->pdo =
                new PDO(
                    (new Dsn(
                        $this->host,
                        $this->port,
                        new NonSpecifiedDatabaseName()
                    ))
                        ->value(),
                    $this->credentials->username(),
                    $this->credentials->password()
                );
        }

        return $this->pdo;
    }
}
