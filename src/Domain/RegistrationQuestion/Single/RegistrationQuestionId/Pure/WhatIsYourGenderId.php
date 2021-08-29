<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

class WhatIsYourGenderId extends RegistrationQuestionId
{
    public function value(): string
    {
        return '305d1c30-26c8-4e12-8a21-9664c73b7c0b';
    }

    public function exists(): bool
    {
        return true;
    }
}