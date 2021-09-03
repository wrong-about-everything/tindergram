<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\RegistrationQuestion\Multiple\Impure\RegistrationQuestions;
use TG\Domain\RegistrationQuestion\Single\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class First extends RegistrationQuestion
{
    private $registrationQuestions;
    private $cached;

    public function __construct(RegistrationQuestions $registrationQuestions)
    {
        $this->registrationQuestions = $registrationQuestions;
        $this->cached = null;
    }

    public function id(): ImpureValue
    {
        return $this->concrete()->id();
    }

    public function ordinalNumber(): ImpureValue
    {
        return $this->concrete()->ordinalNumber();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestion
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): RegistrationQuestion
    {
        if (!$this->registrationQuestions->value()->isSuccessful()) {
            return new NonSuccessful($this->registrationQuestions->value());
        }
        if (empty($this->registrationQuestions->value()->pure()->raw())) {
            return new FromPure(new NonExistent());
        }

        return new FromPure($this->registrationQuestions->value()->pure()->raw()[0]);
    }
}