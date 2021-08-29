<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\ReadModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface RegistrationQuestionAnswer
{
    public function exists(): ImpureValue;
}