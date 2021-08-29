<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Multiple\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

abstract class RegistrationAnswerOptions
{
    abstract public function value(): ImpureValue;

    final public function contain(UserMessage $userMessage): bool
    {
        return in_array($userMessage->value(), $this->value()->pure()->raw());
    }
}