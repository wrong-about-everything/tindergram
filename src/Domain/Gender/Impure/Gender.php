<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Gender
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(Gender $gender): bool
    {
        return $this->value()->pure()->raw() === $gender->value()->pure()->raw();
    }
}