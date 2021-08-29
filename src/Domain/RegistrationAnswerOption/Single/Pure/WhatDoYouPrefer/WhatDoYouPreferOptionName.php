<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer;

abstract class WhatDoYouPreferOptionName
{
    abstract public function value(): string;

    public function equals(WhatDoYouPreferOptionName $name): bool
    {
        return $this->value() === $name->value();
    }
}