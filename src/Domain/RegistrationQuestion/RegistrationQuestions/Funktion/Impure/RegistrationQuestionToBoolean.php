<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Funktion\Impure;

use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestionToBoolean
{
    public function __invoke(RegistrationQuestion $registrationQuestion): ImpureValue/*<Boolean>*/;
}