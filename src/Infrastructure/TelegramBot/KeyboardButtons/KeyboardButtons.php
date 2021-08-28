<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\KeyboardButtons;

interface KeyboardButtons
{
    public function value(): array;
}