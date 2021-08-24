<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Connection;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\DefaultCredentials;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DefaultConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString as PortFromString;
use Throwable;

class DefaultConnectionTest extends TestCase
{
    public function testSuccessfulConnection()
    {
        try {
            $connection =
                new DefaultConnection(
                    new FromString(getenv('DB_HOST')),
                    new PortFromString(getenv('DB_PORT')),
                    new SpecifiedDatabaseName('tg'),
                    new DefaultCredentials('tg', '123456')
                )
            ;
            $pdo = $connection->value();
        } catch (Throwable $e) {
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            return $this->fail('Failed to open connection');
        }

        $this->assertNull($pdo->errorCode());
    }
}
