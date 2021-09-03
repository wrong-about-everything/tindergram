<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class Emptie implements MessageToUser
{
    public function value(): string
    {
        return '';
    }
}