<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure;

use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Pure\WhatIsYourGender;

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
        ][$registrationQuestion->value()]
            ??
        new NonExistentIdWithQuestion($registrationQuestion);
    }
}