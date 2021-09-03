<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;

class FromRegistrationQuestion extends RegistrationQuestionId
{
    private $concrete;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->concrete = $this->concrete($registrationQuestion);
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(RegistrationQuestion $registrationQuestion): RegistrationQuestionId
    {
        return [
            (new WhatDoYouPrefer())->value() => new WhatDoYouPreferId(),
            (new WhatIsYourGender())->value() => new WhatIsYourGenderId(),
            (new AreYouReadyToRegister())->value() => new AreYouReadyToRegisterId(),
        ][$registrationQuestion->value()]
            ??
        new NonExistentIdWithQuestion($registrationQuestion);
    }
}