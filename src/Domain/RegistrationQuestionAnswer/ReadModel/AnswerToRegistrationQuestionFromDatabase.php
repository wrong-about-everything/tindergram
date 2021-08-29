<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\ReadModel;

use Exception;
use TG\Domain\BotUser\Preference\Multiple\Impure\FromBotUser;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\Gender\Impure\FromBotUser as UserGender;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatDoYouPreferId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatIsYourGenderId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class AnswerToRegistrationQuestionFromDatabase implements RegistrationQuestionAnswer
{
    private $registrationQuestion;
    private $botUser;
    private $concrete;

    public function __construct(RegistrationQuestion $registrationQuestion, BotUser $botUser)
    {
        $this->registrationQuestion = $registrationQuestion;
        $this->botUser = $botUser;
        $this->concrete = null;
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestionAnswer
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): RegistrationQuestionAnswer
    {
        switch ((new FromRegistrationQuestion($this->registrationQuestion))->value()) {
            case (new WhatDoYouPreferId())->value():
                return
                    !(new FromBotUser($this->botUser))->value()->isSuccessful()
                        ? new NonSuccessful((new FromBotUser($this->botUser))->value())
                        :
                            (
                                !(new FromBotUser($this->botUser))->value()->pure()->isPresent()
                                    ? new NonExistent()
                                    : new Existent()
                            );

            case (new WhatIsYourGenderId())->value():
                return
                    !(new UserGender($this->botUser))->exists()->isSuccessful()
                        ? new NonSuccessful((new UserGender($this->botUser))->value())
                        :
                            (
                                (new UserGender($this->botUser))->exists()->pure()->raw() === false
                                    ? new NonExistent()
                                    : new Existent()
                            );
        }

        throw
            new Exception(
                sprintf(
                    'Unknown question id: %s',
                    (new FromRegistrationQuestion($this->registrationQuestion))->value()
                )
            );
    }
}