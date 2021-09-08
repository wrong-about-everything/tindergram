<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

use Exception;

class Emptie implements MessageToUser
{
    public function value(): string
    {
        throw new Exception('Message is empty');
    }

    public function isNonEmpty(): bool
    {
        return false;
    }
}