<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction\InlineActionType;

class Rating extends InlineActionType
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}