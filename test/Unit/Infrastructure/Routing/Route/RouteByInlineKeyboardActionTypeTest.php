<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Routing\Route;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Inbound\Composite;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Delete;
use TG\Infrastructure\Http\Request\Method\Get;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Method\Put;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;

class RouteByInlineKeyboardActionTypeTest extends TestCase
{
    /**
     * @dataProvider matchingRoutes
     */
    public function testMatchingRoutes(Route $route, Request $request, array $parsedParamsFromRequest)
    {
        $matchResult = $route->matchResult($request);
        $this->assertTrue($matchResult->matches());
        $this->assertEquals(
            $parsedParamsFromRequest,
            $matchResult->params()
        );
    }

    public function matchingRoutes(): array
    {
        return [
            [
                new RouteByInlineKeyboardActionType(new Get(), '/hello/vasya'),
                new Composite(new Get(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                []
            ],
            [
                new RouteByInlineKeyboardActionType(new Post(), 'hello/:id'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                ['vasya']
            ],
            [
                new RouteByInlineKeyboardActionType(new Delete(), '/:help/vasya'),
                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], ''),
                ['hello']
            ],
            [
                new RouteByInlineKeyboardActionType(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/i/love/you'), [], ''),
                ['i', 'love']
            ],
        ];
    }

    /**
     * @dataProvider nonMatchingRoutes
     */
    public function testNonMatchingRoutes(Route $route, Request $request)
    {
        $matchResult = $route->matchResult($request);
        $this->assertFalse($matchResult->matches());
    }

    public function nonMatchingRoutes(): array
    {
        return [
            [
                new RouteByInlineKeyboardActionType(new Get(), '/hello/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByInlineKeyboardActionType(new Post(), '/hello/:id/vasya'),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByInlineKeyboardActionType(new Delete(), '/help/vasya'),
                new Composite(new Delete(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new RouteByInlineKeyboardActionType(new Put(), '/hello/vasya/:how/:are/you'),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/love/you'), [], '')
            ],
        ];
    }
}
