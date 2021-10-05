<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Routing\Route;

use PHPUnit\Framework\TestCase;
use TG\Domain\InternalApi\RateCallbackData\ThumbsUp;
use TG\Infrastructure\Http\Request\Inbound\Composite;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Delete;
use TG\Infrastructure\Http\Request\Method\Get;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Method\Put;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\Routing\Route\BotUserBannedABotRoute;
use TG\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Stub\TelegramMessage\InlineButtonRatingCallback;
use TG\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessage;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserBannedBotMessage;

class BotUserBannedABotRouteTest extends TestCase
{
    /**
     * @dataProvider matchingRoutes
     */
    public function testMatchingRoutes(Route $route, Request $request, InternalTelegramUserId $internalTelegramUserId)
    {
        $matchResult = $route->matchResult($request);
        $this->assertTrue($matchResult->matches());
        $this->assertEquals(
            $internalTelegramUserId->value(),
            $matchResult->params()[0]->value()
        );
    }

    public function matchingRoutes(): array
    {
        return [
            [
                new BotUserBannedABotRoute(),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    json_encode(
                        (new UserBannedBotMessage($this->telegramUserId()))->value()
                    )
                ),
                $this->telegramUserId()
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
                new BotUserBannedABotRoute(),
                new Composite(new Get(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(
                    new Get(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    json_encode(
                        new UserBannedBotMessage(
                            new FromInteger(1234)
                        )
                    )
                )
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(new Post(), new FromString('https://hello.vasya.ru/hello/vasya'), [], '')
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    json_encode(
                        (new StartCommandMessage(new FromInteger(1234)))->value()
                    )
                )
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    json_encode(
                        (new InlineButtonRatingCallback(
                            new FromInteger(1234),
                            new ThumbsUp(new FromInteger(986))
                        ))
                            ->value()
                    )
                )
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(
                    new Delete(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    json_encode(
                        new UserBannedBotMessage(new FromInteger(1234))
                    )
                )
            ],
            [
                new BotUserBannedABotRoute(),
                new Composite(new Put(), new FromString('https://hello.vasya.ru/hello/vasya/love/you'), [], '')
            ],
        ];
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(5768);
    }
}
