<?php

declare(strict_types=1);

namespace TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection;

use PDO;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString as HostFromString;
use TG\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString as PortFromString;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\ApplicationCredentials;

class ApplicationConnection implements OpenConnection
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = null;
    }

    public function value(): PDO
    {
        if (is_null($this->pdo)) {
            $this->pdo =
                new PDO(
                    (new Dsn(
                        new HostFromString(getenv('DB_HOST')),
                        new PortFromString(getenv('DB_PORT')),
                        new SpecifiedDatabaseName(getenv('DB_NAME'))
                    ))
                        ->value(),
                    (new ApplicationCredentials())->username(),
                    (new ApplicationCredentials())->password()
                );
        }

        return $this->pdo;
    }
}

