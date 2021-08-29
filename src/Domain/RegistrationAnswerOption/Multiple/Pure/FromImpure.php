<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Multiple\Pure;

use TG\Domain\RegistrationAnswerOption\Multiple\Impure\RegistrationAnswerOptions as ImpureRegistrationAnswerOptions;

class FromImpure implements RegistrationAnswerOptions
{
    private $impureRegistrationAnswerOptions;

    public function __construct(ImpureRegistrationAnswerOptions $impureRegistrationAnswerOptions)
    {
        $this->impureRegistrationAnswerOptions = $impureRegistrationAnswerOptions;
    }

    public function value(): array
    {
        return $this->impureRegistrationAnswerOptions->value()->pure()->raw();
    }
}