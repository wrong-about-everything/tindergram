<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class FromString implements RegistrationQuestion
{
    private $registrationQuestionString;
    private $concrete;

    public function __construct(string $registrationQuestionString)
    {
        $this->registrationQuestionString = $registrationQuestionString;
        $this->concrete = null;
    }

    public function value(): string
    {
        return $this->concrete()->value();
    }

    public function ordinalNumber(): int
    {
        return $this->concrete()->ordinalNumber();
    }

    public function exists(): bool
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestion
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): RegistrationQuestion
    {
        return [
            (new WhatDoYouPrefer())->value() => new WhatDoYouPrefer(),
            (new WhatIsYourGender())->value() => new WhatIsYourGender(),
            (new AreYouReadyToRegister())->value() => new AreYouReadyToRegister(),
        ][$this->registrationQuestionString]
            ??
        new NonExistent($this->registrationQuestionString);
    }
}