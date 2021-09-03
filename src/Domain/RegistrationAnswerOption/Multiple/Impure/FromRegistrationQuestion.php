<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Multiple\Impure;

use Exception;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\Register;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Women;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Female;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Impure\FromRegistrationQuestion as RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\AreYouReadyToRegisterId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatDoYouPreferId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatIsYourGenderId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromRegistrationQuestion extends RegistrationAnswerOptions
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
                return new Successful(new Present([(new Men())->value(), (new Women())->value()]));

            case (new WhatIsYourGenderId())->value():
                return new Successful(new Present([(new Male())->value(), (new Female())->value()]));

            case (new AreYouReadyToRegisterId())->value():
                return new Successful(new Present([(new Register())->value()]));
        }

        throw new Exception(sprintf('Registration question "%s" is unknown', $this->registrationQuestion->value()->pure()->raw()));
    }
}