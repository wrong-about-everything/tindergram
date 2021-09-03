<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

class AreYouReadyToRegisterId extends RegistrationQuestionId
{
    public function value(): string
    {
        return '2f71f0bc-9dcb-4e6a-aab6-d8ce61a0c26d';
    }

    public function exists(): bool
    {
        return true;
    }
}