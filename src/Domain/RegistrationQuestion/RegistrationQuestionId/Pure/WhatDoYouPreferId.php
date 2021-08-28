<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure;

class WhatDoYouPreferId extends RegistrationQuestionId
{
    public function value(): string
    {
        return '5dc1cca5-0d51-4e26-b46c-6f1251afb2b3';
    }

    public function exists(): bool
    {
        return true;
    }
}