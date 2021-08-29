<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Impure;

use Closure;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class Ordered implements RegistrationQuestions
{
    private $registrationQuestions;
    private $sortingFunction;

    public function __construct(RegistrationQuestions $registrationQuestions, Closure $sortingFunction)
    {
        $this->registrationQuestions = $registrationQuestions;
        $this->sortingFunction = $sortingFunction;
    }

    /**
     * @inheritDoc
     */
    public function value(): ImpureValue
    {
        $registrationQuestions = $this->registrationQuestions->value();
        if (!$registrationQuestions->isSuccessful()) {
            return $registrationQuestions;
        }

        $rawQuestions = $registrationQuestions->pure()->raw();
        usort(
            $rawQuestions,
            function (RegistrationQuestion $left, RegistrationQuestion $right) {
                return ($this->sortingFunction)($left, $right);
            }
        );

        return new Successful(new Present($rawQuestions));
    }
}