<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Pure;

abstract class PreferenceIds
{
    abstract public function value(): array;

    final public function overlappedWith(PreferenceIds $with): array
    {
        return array_intersect($this->value(), $with->value());
    }
}