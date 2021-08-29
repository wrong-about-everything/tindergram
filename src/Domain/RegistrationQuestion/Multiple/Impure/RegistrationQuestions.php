<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Impure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestions
{
    /**
     * @return ImpureValue<RegistrationQuestion[]>
     */
    public function value(): ImpureValue;
}