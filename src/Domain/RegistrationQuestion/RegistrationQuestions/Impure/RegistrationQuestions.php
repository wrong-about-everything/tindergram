<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Impure;

use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestions
{
    /**
     * @return ImpureValue<RegistrationQuestion[]>
     */
    public function value(): ImpureValue;
}