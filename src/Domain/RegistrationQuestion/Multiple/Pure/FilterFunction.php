<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Pure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Infrastructure\Funktion\Pure\ReturningBool;

class FilterFunction implements ReturningBool
{
    private $registrationQuestion;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
    }

    public function value(): bool
    {
        // TODO: Implement value() method.
    }
}