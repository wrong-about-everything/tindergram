<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple\LinedUpInARow;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single\WithCallbackData;

class LinedUpInARowTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(
            [
                [
                    [
                        'text' => 'привет',
                        'callback_data' => json_encode(['vasya']),
                    ],
                    [
                        'text' => 'hello',
                        'callback_data' => json_encode(['Василий']),
                    ],
                    [
                        'text' => 'hey',
                        'callback_data' => json_encode(['there']),
                    ],
                ]
            ],
            (new LinedUpInARow([
                new WithCallbackData('привет', ['vasya']),
                new WithCallbackData('hello', ['Василий']),
                new WithCallbackData('hey', ['there']),
            ]))
                ->value()
        );
    }
}