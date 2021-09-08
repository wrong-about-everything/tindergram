<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use Exception;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class FromQuestion implements MessageToUser
{
    private $registrationQuestion;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
    }

    public function value(): string
    {
        switch ($this->registrationQuestion->id()) {
            case (new WhatDoYouPrefer())->id():
                return 'Какие аккаунты вам показывать?';

            case (new WhatIsYourGender())->id():
                return 'Укажите свой пол';
        }

        throw new Exception(sprintf('Unknown question text for question id = %s', $this->registrationQuestion->id()));
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}