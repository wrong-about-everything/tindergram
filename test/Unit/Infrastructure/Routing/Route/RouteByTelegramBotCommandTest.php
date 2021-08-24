<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Routing\Route;

use PHPUnit\Framework\TestCase;
use TG\Domain\TelegramBot\UserCommand\FromString as CommandFromString;
use TG\Infrastructure\Http\Request\Inbound\Composite;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Get;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\Routing\Route\RouteByTelegramBotCommand;
use TG\Infrastructure\TelegramBot\UserCommand\Start;
use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

class RouteByTelegramBotCommandTest extends TestCase
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
                new RouteByTelegramBotCommand(new Start()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya?secret_smile=c0139e5f-24b5-4b9e-bb41-0ac7909de2f7'),
                    [],
                    $this->requestBody(new Start())
                ),
                [json_decode($this->requestBody(new Start()),true), 'c0139e5f-24b5-4b9e-bb41-0ac7909de2f7']
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
                new RouteByTelegramBotCommand(new Start()),
                new Composite(
                    new Get(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    $this->requestBody(new Start())
                )
            ],
            [
                new RouteByTelegramBotCommand(new Start()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    $this->requestBody(new CommandFromString('/hello'))
                )
            ],
            [
                new RouteByTelegramBotCommand(new Start()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    $this->invalidRequestBody()
                )
            ],
        ];
    }

    private function requestBody(UserCommand $command)
    {
        return
            sprintf(
                <<<q
{
    "update_id": 814185830,
    "message": {
        "message_id": 726138,
        "from": {
            "id": 245192624,
            "is_bot": false,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo"
        },
        "chat": {
            "id": 245192624,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo",
            "type": "private"
        },
        "date": 1625481534,
        "text": "%s",
        "entities": [
            {
                "offset": 0,
                "length": 6,
                "type": "bot_command"
            }
        ]
    }
}
q
                ,
                $command->exists() ? $command->value() : 'unknown_command'
            );
    }

    private function invalidRequestBody()
    {
        return 'hello!';
    }
}
