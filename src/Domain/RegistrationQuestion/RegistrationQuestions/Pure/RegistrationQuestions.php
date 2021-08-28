<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Pure;

use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;

interface RegistrationQuestions
{
    /**
     * @return RegistrationQuestion[]
     */
    public function value(): array;
}