<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Pure;

class WhatDoYouPrefer implements RegistrationQuestion
{
    public function value(): string
    {
        return 'Какие аккаунты вам показывать?';
    }

    public function ordinalNumber(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}