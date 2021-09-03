<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer;

use Exception;
use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromString as UserMessage;

class FromString extends WhatDoYouPreferOptionName
{
    private $whatDoYouPreferOptionName;

    public function __construct(string $whatDoYouPreferOptionName)
    {
        if (!(new FromRegistrationQuestion(new FromPure(new WhatDoYouPrefer())))->contain(new UserMessage($whatDoYouPreferOptionName))) {
            throw new Exception(sprintf('У вопроса "%s" нет варианта ответа "%s"', (new WhatDoYouPrefer())->id(), $whatDoYouPreferOptionName));
        }

        $this->whatDoYouPreferOptionName = $whatDoYouPreferOptionName;
    }

    public function value(): string
    {
        return $this->whatDoYouPreferOptionName;
    }
}