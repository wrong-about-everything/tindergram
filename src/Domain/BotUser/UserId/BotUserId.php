<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserId;

abstract class BotUserId
{
    abstract public function value(): string;

    final public function equals(BotUserId $botId): bool
    {
        return $this->value() === $botId->value();
    }
}