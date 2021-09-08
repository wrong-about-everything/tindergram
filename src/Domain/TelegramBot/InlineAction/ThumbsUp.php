<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

class ThumbsUp extends InlineAction
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