<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class ById implements RegistrationQuestion
{
    private $registrationQuestionString;
    private $concrete;

    public function __construct(string $registrationQuestionId)
    {
        $this->registrationQuestionString = $registrationQuestionId;
        $this->concrete = null;
    }

    public function id(): string
    {
        return $this->concrete()->id();
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
            (new WhatDoYouPrefer())->id() => new WhatDoYouPrefer(),
            (new WhatIsYourGender())->id() => new WhatIsYourGender(),
            (new AreYouReadyToRegister())->id() => new AreYouReadyToRegister(),
        ][$this->registrationQuestionString]
            ??
        new NonExistentWithId($this->registrationQuestionString);
    }
}