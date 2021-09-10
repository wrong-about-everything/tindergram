<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction\InlineActionType;

use Exception;

class NonExistent extends InlineActionType
{
    public function value(): int
    {
        throw new Exception('Inline action type does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}