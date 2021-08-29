<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\ReadModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class Existent implements RegistrationQuestionAnswer
{
    public function exists(): ImpureValue
    {
        return new Successful(new Present(true));
    }
}