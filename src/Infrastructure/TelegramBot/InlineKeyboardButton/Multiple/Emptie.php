<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple;

class Emptie implements InlineKeyboardButtons
{
    public function value(): array
    {
        return [];
    }
}