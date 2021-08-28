<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationAnswerOptions
{
    public function value(): ImpureValue;
}