<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\SqlDatabase\Concrete\Postgres\Connection;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\NonSpecifiedDatabaseName;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use TG\Infrastructure\SqlDatabase\Concrete\Postgres\Connection\Dsn;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port\DefaultPort;
use TG\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString as SpecifiedPort;

class DsnTest extends TestCase
{
    public function testFullDsn()
    {
        $this->assertEquals(
            'pgsql:host=localcoast;port=5432;dbname=vasya',
            (new Dsn(
                new FromString('localcoast'),
                new SpecifiedPort('5432'),
                new SpecifiedDatabaseName('vasya')
            ))
                ->value()
        );
    }

    public function testDsnWithNotSpecifiedDatabaseAndDefaultPort()
    {
        $this->assertEquals(
            'pgsql:host=localcoast',
            (new Dsn(
                new FromString('localcoast'),
                new DefaultPort(),
                new NonSpecifiedDatabaseName()
            ))
                ->value()
        );
    }

    public function testDsnWithSpecifiedDatabaseAndDefaultPort()
    {
        $this->assertEquals(
            'pgsql:host=localcoast;dbname=vasya',
            (new Dsn(
                new FromString('localcoast'),
                new DefaultPort(),
                new SpecifiedDatabaseName('vasya')
            ))
                ->value()
        );
    }

    public function testDsnWithNotSpecifiedDatabaseAndSpecifiedPort()
    {
        $this->assertEquals(
            'pgsql:host=localcoast;port=5432',
            (new Dsn(
                new FromString('localcoast'),
                new SpecifiedPort('5432'),
                new NonSpecifiedDatabaseName()
            ))
                ->value()
        );
    }
}
