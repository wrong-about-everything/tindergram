<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure;

abstract class RegistrationQuestionId
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(RegistrationQuestionId $to): bool
    {
        return $this->value() === $to->value();
    }
}