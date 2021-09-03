<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

use Exception;

class NonExistent implements RegistrationQuestion
{
    public function id(): string
    {
        throw new Exception('Question does not exist');
    }

    public function ordinalNumber(): int
    {
        throw new Exception('Question does not eixist');
    }

    public function exists(): bool
    {
        return false;
    }
}