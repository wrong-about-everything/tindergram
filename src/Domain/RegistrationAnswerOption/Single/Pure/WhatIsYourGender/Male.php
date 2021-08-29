<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender;

class Male extends WhatIsYourGenderOptionName
{
    public function value(): string
    {
        return 'Мужской';
    }
}