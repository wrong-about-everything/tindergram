<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

interface RegistrationQuestion
{
    public function value(): string;

    public function ordinalNumber(): int;

    public function exists(): bool;
}