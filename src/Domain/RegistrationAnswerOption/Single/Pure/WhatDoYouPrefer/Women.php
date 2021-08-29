<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer;

class Women extends WhatDoYouPreferOptionName
{
    public function value(): string
    {
        return 'Женские';
    }
}