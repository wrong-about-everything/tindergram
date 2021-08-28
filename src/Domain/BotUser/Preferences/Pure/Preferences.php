<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preferences\Pure;

abstract class Preferences
{
    abstract public function value(): array;

    final public function overlappedWith(Preferences $with): array
    {
        return array_intersect($this->value(), $with->value());
    }
}