<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\KeyboardButtons;

class Emptie implements KeyboardButtons
{
    public function value(): array
    {
        return [];
    }
}