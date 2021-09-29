<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Mode
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(Mode $mode): bool
    {
        return $this->value()->pure()->raw() === $mode->value()->pure()->raw();
    }
}