<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Pure;

use TG\Domain\RegistrationQuestion\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Pure\WhatIsYourGender;

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
        ];
    }
}