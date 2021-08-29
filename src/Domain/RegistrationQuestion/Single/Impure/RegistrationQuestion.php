<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestion
{
    public function value(): ImpureValue;
}