<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

interface RegistrationQuestion
{
    public function id(): string;

    public function ordinalNumber(): int;

    public function exists(): bool;
}