<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

abstract class InlineAction
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(InlineAction $action): bool
    {
        return $this->value() === $action->value();
    }
}