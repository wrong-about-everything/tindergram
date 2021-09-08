<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single;

interface InlineKeyboardButton
{
    public function value(): array;

    public function isEmpty(): bool;
}