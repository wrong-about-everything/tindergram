<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Routing\Route;

use PHPUnit\Framework\TestCase;
use TG\Domain\InternalApi\RateCallbackData\ThumbsUp;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\FromInteger as ActionType;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\Rating;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\TestCallbackType;
use TG\Infrastructure\Http\Request\Inbound\Composite;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Get;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\Routing\Route\RouteByInlineKeyboardActionType;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Stub\TelegramMessage\InlineButtonCallbackWithUnknownType;
use TG\Tests\Infrastructure\Stub\TelegramMessage\InlineButtonRatingCallback;

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
            [$matchResult->params()[0]->value(), $matchResult->params()[1]->value(), ]
        );
    }

    public function matchingRoutes(): array
    {
        return [
            [
                new RouteByInlineKeyboardActionType(new Rating()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    (new InlineButtonRatingCallback($this->telegramUserId(), new ThumbsUp($this->pairTelegramId())))->value()
                ),
                [$this->telegramUserId()->value(), (new ThumbsUp($this->pairTelegramId()))->value()]
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
                new RouteByInlineKeyboardActionType(new Rating()),
                new Composite(
                    new Get(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    (new InlineButtonRatingCallback($this->telegramUserId(), new ThumbsUp($this->pairTelegramId())))->value()
                ),
                [$this->telegramUserId()->value(), (new ThumbsUp($this->pairTelegramId()))->value()]
            ],
            [
                new RouteByInlineKeyboardActionType(new Rating()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    'hello, vasya!'
                ),
                [$this->telegramUserId()->value(), (new ThumbsUp($this->pairTelegramId()))->value()]
            ],
            [
                new RouteByInlineKeyboardActionType(new Rating()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    (new InlineButtonCallbackWithUnknownType($this->telegramUserId()))->value()
                ),
            ],
            [
                new RouteByInlineKeyboardActionType(new TestCallbackType()),
                new Composite(
                    new Post(),
                    new FromString('https://hello.vasya.ru/hello/vasya'),
                    [],
                    (new InlineButtonRatingCallback($this->telegramUserId(), new ThumbsUp($this->pairTelegramId())))->value()
                ),
            ],
        ];
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(1111111111);
    }

    private function pairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(222222222222);
    }
}
