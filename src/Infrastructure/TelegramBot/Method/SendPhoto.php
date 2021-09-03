<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\Method;

class SendPhoto extends Method
{
    public function value(): string
    {
        return 'sendPhoto';
    }
}