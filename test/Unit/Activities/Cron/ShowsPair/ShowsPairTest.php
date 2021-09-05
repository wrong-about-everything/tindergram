<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron\ShowsPair;

use PHPUnit\Framework\TestCase;
use TG\Activities\Cron\ShowsPair\ShowsPair;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs\StdOut;
use TG\Infrastructure\Uuid\RandomUUID;

class ShowsPairTest extends TestCase
{
    public function test()
    {
        $transport = new Indifferent();
        $connection = new ApplicationConnection();
        $response = (new ShowsPair($transport, $connection, new StdOut(new LogId(new RandomUUID()))))->response();

    }
}