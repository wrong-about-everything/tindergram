<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

class ThumbsDown extends InlineAction
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}