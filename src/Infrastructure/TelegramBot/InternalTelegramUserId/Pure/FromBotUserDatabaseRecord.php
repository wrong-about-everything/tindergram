<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

class FromBotUserDatabaseRecord extends InternalTelegramUserId
{
    private $concrete;

    public function __construct(array $botUserDatabaseRecord)
    {
        $this->concrete = new FromInteger($botUserDatabaseRecord['telegram_id']);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return true;
    }
}