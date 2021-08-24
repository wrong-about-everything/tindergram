<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserMessage\Pure;

use Exception;

class NonExistent extends UserMessage
{
    public function value(): string
    {
        throw new Exception('User message does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}