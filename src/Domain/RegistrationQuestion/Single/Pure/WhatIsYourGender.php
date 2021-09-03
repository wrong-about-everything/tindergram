<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class WhatIsYourGender implements RegistrationQuestion
{
    public function id(): string
    {
        return '305d1c30-26c8-4e12-8a21-9664c73b7c0b';
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