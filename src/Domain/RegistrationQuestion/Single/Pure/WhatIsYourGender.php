<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class WhatIsYourGender implements RegistrationQuestion
{
    public function value(): string
    {
        return 'Укажите свой пол';
    }

    public function ordinalNumber(): int
    {
        return 2;
    }

    public function exists(): bool
    {
        return true;
    }
}