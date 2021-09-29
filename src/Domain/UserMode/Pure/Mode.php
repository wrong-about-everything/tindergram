<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

abstract class Mode
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(Mode $mode): bool
    {
        return $this->value() === $mode->value();
    }
}