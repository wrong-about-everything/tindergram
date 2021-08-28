<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestionAnswer
{
    public function exists(): ImpureValue;
}