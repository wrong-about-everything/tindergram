<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister;

abstract class AreYouReadyToRegisterOptionName
{
    abstract public function value(): string;

    final public function equals(AreYouReadyToRegisterOptionName $optionName): bool
    {
        return $this->value() === $optionName->value();
    }
}