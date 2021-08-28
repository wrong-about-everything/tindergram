<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class RegistrationQuestionId
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(RegistrationQuestionId $to): bool
    {
        return $this->value()->pure()->raw() === $to->value()->pure()->raw();
    }
}