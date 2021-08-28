<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Impure;

use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\RegistrationQuestions\Funktion\Impure\RegistrationQuestionToBoolean;
use TG\Domain\RegistrationQuestion\RegistrationQuestions\Pure\RegistrationQuestions as PureRegistrationQuestions;
use TG\Infrastructure\Exception\StateCarrying;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FilteredOut implements RegistrationQuestions
{
    private $registrationQuestions;
    private $filterFunction;

    public function __construct(PureRegistrationQuestions $registrationQuestions, RegistrationQuestionToBoolean $filterFunction)
    {
        $this->registrationQuestions = $registrationQuestions;
        $this->filterFunction = $filterFunction;
    }

    /**
     * @inheritDoc
     */
    public function value(): ImpureValue
    {
        try {
            return
                new Successful(
                    new Present(
                        array_filter(
                            $this->registrationQuestions->value(),
                            function (RegistrationQuestion $registrationQuestion) {
                                $functionValue = ($this->filterFunction)($registrationQuestion);
                                if (!$functionValue->isSuccessful()) {
                                    throw new StateCarrying($functionValue->error());
                                }

                                return !$functionValue->pure()->raw();
                            }
                        )
                    )
                );
        } catch (StateCarrying $e) {
            return new Failed($e->error());
        }
    }
}