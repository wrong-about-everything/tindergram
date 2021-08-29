<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\WriteModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestionAnswer
{
    public function value(): ImpureValue;
}