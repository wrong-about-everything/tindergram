<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent implements RegistrationQuestionAnswer
{
    public function exists(): ImpureValue
    {
        return new Successful(new Present(false));
    }
}