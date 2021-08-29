<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Pure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;

interface RegistrationQuestions
{
    /**
     * @return RegistrationQuestion[]
     */
    public function value(): array;
}