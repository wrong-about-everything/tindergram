<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Impure;

use TG\Domain\RegistrationQuestion\RegistrationQuestions\Impure\RegistrationQuestions;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class First implements RegistrationQuestion
{
    private $registrationQuestions;

    public function __construct(RegistrationQuestions $registrationQuestions)
    {
        $this->registrationQuestions = $registrationQuestions;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestions->value()->isSuccessful() || empty($this->registrationQuestions->value()->pure()->raw())) {
            return $this->registrationQuestions->value();
        }

        return new Successful(new Present($this->registrationQuestions->value()->pure()->raw()[0]->value()));
    }
}