<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Impure;

use Exception;
use TG\Domain\RegistrationQuestion\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\FromRegistrationQuestion as RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\WhatDoYouPreferId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\WhatIsYourGenderId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromRegistrationQuestion implements RegistrationAnswerOptions
{
    private $registrationQuestion;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        switch ((new RegistrationQuestionId($this->registrationQuestion))->value()->pure()->raw()) {
            case (new WhatDoYouPreferId())->value():
                return new Successful(new Present(['Мужские', 'Женские']));

            case (new WhatIsYourGenderId())->value():
                return new Successful(new Present(['Мужской', 'Женский']));
        }

        throw new Exception(sprintf('Registration question "%s" is unknown', $this->registrationQuestion->value()->pure()->raw()));
    }
}