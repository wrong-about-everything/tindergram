<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\ReadModel;

use Exception;
use TG\Domain\Gender\Impure\BotUserPreferredGender;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser as StatusFromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\Gender\Impure\FromBotUser as UserGender;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
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
        switch ($this->registrationQuestion->id()) {
            case (new WhatDoYouPrefer())->id():
                $preferredGender = new BotUserPreferredGender($this->botUser);
                return
                    !$preferredGender->exists()->isSuccessful()
                        ? new NonSuccessful($preferredGender->exists())
                        :
                            (
                                !$preferredGender->exists()->pure()->raw()
                                    ? new NonExistent()
                                    : new Existent()
                            );

            case (new WhatIsYourGender())->id():
                return
                    !(new UserGender($this->botUser))->exists()->isSuccessful()
                        ? new NonSuccessful((new UserGender($this->botUser))->value())
                        :
                            (
                                (new UserGender($this->botUser))->exists()->pure()->raw() === false
                                    ? new NonExistent()
                                    : new Existent()
                            );

            case (new AreYouReadyToRegister())->id():
                return
                    (new StatusFromBotUser($this->botUser))->equals(new FromPure(new RegistrationIsInProgress()))
                        ? new NonExistent()
                        : new Existent();
        }

        throw
            new Exception(
                sprintf(
                    'Unknown question id: %s',
                    $this->registrationQuestion->id()
                )
            );
    }
}