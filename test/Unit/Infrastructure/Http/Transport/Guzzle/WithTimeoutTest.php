<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use TG\Infrastructure\Http\Transport\Guzzle\WithTimeout;
use TG\Tests\Infrastructure\Http\Transport\Guzzle\ClientStub;
use TG\Tests\Infrastructure\Http\Transport\Guzzle\GuzzleWithPublicOptions;

class WithTimeoutTest extends TestCase
{
    public function testSuccess()
    {
        $guzzle =
            new GuzzleWithPublicOptions(
                new WithTimeout(
                    new DefaultGuzzle(
                        new ClientStub(new Response())
                    ),
                    25
                )
            );

        $response =
            $guzzle
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
        $this->assertEquals(25, $guzzle->options()[RequestOptions::TIMEOUT]);
    }
}