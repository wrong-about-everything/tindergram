<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Pure;

interface RegistrationAnswerOptions
{
    public function value(): array;
}