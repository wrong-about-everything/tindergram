<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\StandardKeyboardButtons;

class Emptie implements StandardKeyboardButtons
{
    public function value(): array
    {
        return [];
    }
}