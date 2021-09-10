<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineKeyboardButton\Single;

use TG\Domain\InternalApi\RateCallbackData\ThumbsDown as ThumbsDownCallbackData;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single\InlineKeyboardButton;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single\WithCallbackData;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class ThumbsDown implements InlineKeyboardButton
{
    private $concrete;

    public function __construct(InternalTelegramUserId $pairTelegramId)
    {
        $this->concrete =
            new WithCallbackData(
                '👎',
                (new ThumbsDownCallbackData($pairTelegramId))->value()
            );
    }

    public function value(): array
    {
        return $this->concrete->value();
    }

    public function isEmpty(): bool
    {
        return $this->concrete->isEmpty();
    }
}