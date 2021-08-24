<?php

declare(strict_types=1);

namespace TG\Domain\TelegramUser\UserId;

abstract class TelegramUserId
{
    abstract public function value(): string;

    final public function equals(TelegramUserId $botId): bool
    {
        return $this->value() === $botId->value();
    }
}