<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction\InlineActionType;

abstract class InlineActionType
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(InlineActionType $type): bool
    {
        return $this->value() === $type->value();
    }
}