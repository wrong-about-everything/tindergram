<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class AreYouReadyToRegister implements RegistrationQuestion
{
    public function id(): string
    {
        return '2f71f0bc-9dcb-4e6a-aab6-d8ce61a0c26d';
    }

    public function ordinalNumber(): int
    {
        return 3;
    }

    public function exists(): bool
    {
        return true;
    }
}