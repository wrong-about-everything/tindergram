<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Impure;

use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\RegistrationQuestionId as PureRegistrationQuestionId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends RegistrationQuestionId
{
    private $pureRegistrationQuestionId;

    public function __construct(PureRegistrationQuestionId $pureRegistrationQuestionId)
    {
        $this->pureRegistrationQuestionId = $pureRegistrationQuestionId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestionId->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestionId->exists()));
    }
}