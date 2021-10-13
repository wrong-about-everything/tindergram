<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Multiple\Impure;

use Exception;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\RegisterInInvisibleMode;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\RegisterInVisibleMode;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Women;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Female;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromRegistrationQuestion extends RegistrationAnswerOptions
{
    private $registrationQuestionId;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestionId = $registrationQuestion;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestionId->id()->isSuccessful()) {
            return $this->registrationQuestionId->id();
        }

        switch ($this->registrationQuestionId->id()->pure()->raw()) {
            case (new WhatDoYouPrefer())->id():
                return new Successful(new Present([(new Men())->value(), (new Women())->value()]));

            case (new WhatIsYourGender())->id():
                return new Successful(new Present([(new Male())->value(), (new Female())->value()]));

            case (new AreYouReadyToRegister())->id():
                return new Successful(new Present([(new RegisterInVisibleMode())->value()]));
        }

        throw new Exception(sprintf('Registration question "%s" is unknown', $this->registrationQuestionId->id()->pure()->raw()));
    }
}