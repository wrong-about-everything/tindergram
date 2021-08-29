<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender;

use Exception;
use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromString as UserMessage;

class FromString extends WhatIsYourGenderOptionName
{
    private $whatIsYourGenderOptionName;

    public function __construct(string $whatIsYourGenderOptionName)
    {
        if (!(new FromRegistrationQuestion(new FromPure(new WhatIsYourGender())))->contain(new UserMessage($whatIsYourGenderOptionName))) {
            throw new Exception(sprintf('У вопроса "%s" нет варианта ответа "%s"', (new WhatIsYourGender())->value(), $whatIsYourGenderOptionName));
        }

        $this->whatIsYourGenderOptionName = $whatIsYourGenderOptionName;
    }

    public function value(): string
    {
        return $this->whatIsYourGenderOptionName;
    }
}