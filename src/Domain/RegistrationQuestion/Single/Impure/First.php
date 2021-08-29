<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\RegistrationQuestion\Multiple\Impure\RegistrationQuestions;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class First implements RegistrationQuestion
{
    private $registrationQuestions;
    private $cached;

    public function __construct(RegistrationQuestions $registrationQuestions)
    {
        $this->registrationQuestions = $registrationQuestions;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->registrationQuestions->value()->isSuccessful() || empty($this->registrationQuestions->value()->pure()->raw())) {
            return $this->registrationQuestions->value();
        }

        return new Successful(new Present($this->registrationQuestions->value()->pure()->raw()[0]->value()));
    }
}