<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

abstract class Gender
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(Gender $gender): bool
    {
        return $this->value() === $gender->value();
    }
}