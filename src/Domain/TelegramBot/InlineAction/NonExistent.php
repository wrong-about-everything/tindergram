<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

use Exception;

class NonExistent extends InlineAction
{
    public function value(): int
    {
        throw new Exception('This inline action does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}