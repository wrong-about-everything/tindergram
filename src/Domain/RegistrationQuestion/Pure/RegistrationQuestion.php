<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Pure;

interface RegistrationQuestion
{
    public function value(): string;

    public function ordinalNumber(): int;

    public function exists(): bool;
}