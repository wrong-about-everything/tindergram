<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister;

class RegisterInInvisibleMode extends AreYouReadyToRegisterOptionName
{
    public function value(): string
    {
        return 'Зарегистрироваться в режиме невидимки 🕵️';
    }
}