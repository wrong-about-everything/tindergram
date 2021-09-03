<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class WhatDoYouPrefer implements RegistrationQuestion
{
    public function id(): string
    {
        return '5dc1cca5-0d51-4e26-b46c-6f1251afb2b3';
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