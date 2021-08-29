<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Multiple\Pure;

interface RegistrationAnswerOptions
{
    public function value(): array;
}