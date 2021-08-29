<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Impure;

use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\FromString;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\FromRegistrationQuestion as PureRegistrationQuestionId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class FromRegistrationQuestion extends RegistrationQuestionId
{
    private $registrationQuestion;
    private $concrete;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestionId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): RegistrationQuestionId
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return new NonSuccessful($this->registrationQuestion->value());
        }
        if (!$this->registrationQuestion->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new PureRegistrationQuestionId(new FromString($this->registrationQuestion->value()->pure()->raw())));
    }
}