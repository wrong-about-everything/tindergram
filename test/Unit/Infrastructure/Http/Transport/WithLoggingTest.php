<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Transport;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Transport\WithLogging;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs\InMemory;
use TG\Infrastructure\Uuid\FromString as UuidFromString;
use TG\Tests\Infrastructure\Http\Transport\FakeWithCodeHeadersAndBody;
use TG\Tests\Infrastructure\Http\Transport\Sleepy;

class WithLoggingTest extends TestCase
{
    public function testResponseLogged()
    {
        $logs =
            new InMemory(
                new LogId(new UuidFromString(Uuid::uuid4()->toString()))
            );

        $transport =
            new WithLogging(
                new Sleepy(
                    new FakeWithCodeHeadersAndBody(new Ok(), [], ''),
                    10
                ),
                $logs
            );

        $transport->response(new OutboundRequest(new Post(), new FromString('http://vasya.ru'), [], 'vasya'));

        $this->assertNotEmpty($logs->all());
        $this->assertCount(2, $logs->all());
    }
}
