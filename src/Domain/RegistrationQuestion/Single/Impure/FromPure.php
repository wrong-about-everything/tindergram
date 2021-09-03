<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion as PureRegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends RegistrationQuestion
{
    private $pureRegistrationQuestion;

    public function __construct(PureRegistrationQuestion $pureRegistrationQuestion)
    {
        $this->pureRegistrationQuestion = $pureRegistrationQuestion;
    }

    public function id(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestion->id()));
    }

    public function ordinalNumber(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestion->ordinalNumber()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestion->exists()));
    }
}