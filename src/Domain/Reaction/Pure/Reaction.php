<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

abstract class Reaction
{
    abstract function value(): int;

    abstract public function exists(): bool;

    final public function equals(Reaction $reaction): bool
    {
        return $this->value() === $reaction->value();
    }
}