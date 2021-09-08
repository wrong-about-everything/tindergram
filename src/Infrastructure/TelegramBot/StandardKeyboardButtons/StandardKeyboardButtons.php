<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\StandardKeyboardButtons;

interface StandardKeyboardButtons
{
    public function value(): array;
}