<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

use Exception;

class NonExistent extends InternalTelegramUserId
{
    public function value(): int
    {
        throw new Exception('User id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}