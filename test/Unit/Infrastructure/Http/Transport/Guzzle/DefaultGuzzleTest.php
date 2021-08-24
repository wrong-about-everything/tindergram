<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use TG\Tests\Infrastructure\Http\Transport\Guzzle\ClientStub;

class DefaultGuzzleTest extends TestCase
{
    public function testSuccess()
    {
        $response =
            (new DefaultGuzzle(
                new ClientStub(
                    new Response(200, [], '')
                )
            ))
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new FromString('http://vasya.ru'),
                        [],
                        ''
                    )
                )
        ;

        $this->assertTrue($response->isAvailable());
        $this->assertEquals(200, $response->code()->value());
        $this->assertEquals('', $response->body());
        $this->assertEquals([], $response->headers());
    }
}