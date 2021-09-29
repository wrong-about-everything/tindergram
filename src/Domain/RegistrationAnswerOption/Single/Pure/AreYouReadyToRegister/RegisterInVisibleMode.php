<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister;

class RegisterInVisibleMode extends AreYouReadyToRegisterOptionName
{
    public function value(): string
    {
        return 'Поехали 🚀';
    }
}