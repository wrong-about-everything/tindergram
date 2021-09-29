<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

use Exception;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\AreYouReadyToRegisterOptionName;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\RegisterInInvisibleMode;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\RegisterInVisibleMode;

class FromAreYouReadyToRegisterOptionName extends Mode
{
    private $concrete;

    public function __construct(AreYouReadyToRegisterOptionName $optionName)
    {
        $this->concrete = $this->concrete($optionName);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(AreYouReadyToRegisterOptionName $optionName): Mode
    {
        if ($optionName->equals(new RegisterInVisibleMode())) {
            return new Visible();
        } elseif ($optionName->equals(new RegisterInInvisibleMode())) {
            return new Invisible();
        }

        throw new Exception(sprintf('Unknown option name given: %s', $optionName));
    }
}