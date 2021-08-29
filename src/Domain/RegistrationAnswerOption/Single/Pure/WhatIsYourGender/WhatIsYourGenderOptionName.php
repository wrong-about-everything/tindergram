<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender;

abstract class WhatIsYourGenderOptionName
{
    abstract public function value(): string;

    final public function equals(WhatIsYourGenderOptionName $genderOptionName): bool
    {
        return $this->value() === $genderOptionName->value();
    }
}