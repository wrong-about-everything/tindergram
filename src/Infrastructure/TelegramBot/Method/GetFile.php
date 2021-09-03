<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\Method;

class GetFile extends Method
{
    public function value(): string
    {
        return 'getFile';
    }
}