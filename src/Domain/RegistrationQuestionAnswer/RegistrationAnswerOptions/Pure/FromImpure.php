<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Pure;

use TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Impure\RegistrationAnswerOptions as ImpureRegistrationAnswerOptions;

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