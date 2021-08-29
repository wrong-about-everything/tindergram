<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Single\Pure;

abstract class PreferenceId
{
    abstract public function value(): int;

    final public function equals(PreferenceId $another): bool
    {
        return $this->value() === $another->value();
    }
}