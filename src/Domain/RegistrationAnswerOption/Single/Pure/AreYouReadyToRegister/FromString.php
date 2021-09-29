<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister;

use Exception;
use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromString as UserMessage;

class FromString extends AreYouReadyToRegisterOptionName
{
    private $optionName;

    public function __construct(string $optionName)
    {
        if (!(new FromRegistrationQuestion(new FromPure(new AreYouReadyToRegister())))->contain(new UserMessage($optionName))) {
            throw new Exception(sprintf('У вопроса "%s" нет варианта ответа "%s"', (new AreYouReadyToRegister())->id(), $optionName));
        }

        $this->optionName = $optionName;
    }

    public function value(): string
    {
        return $this->optionName;
    }
}