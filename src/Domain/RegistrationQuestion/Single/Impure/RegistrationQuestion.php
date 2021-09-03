<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class RegistrationQuestion
{
    abstract public function id(): ImpureValue;

    abstract public function ordinalNumber(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(RegistrationQuestion $registrationQuestionId): bool
    {
        return $this->id()->pure()->raw() === $registrationQuestionId->id()->pure()->raw();
    }
}