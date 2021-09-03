<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Pure;

use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;

class All implements RegistrationQuestions
{
    /**
     * @inheritDoc
     */
    public function value(): array
    {
        return [
            new WhatDoYouPrefer(),
            new WhatIsYourGender(),
            new AreYouReadyToRegister(),
        ];
    }
}