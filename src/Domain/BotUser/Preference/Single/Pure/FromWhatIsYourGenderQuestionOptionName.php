<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Single\Pure;

use Exception;
use TG\Domain\Gender\Pure\Female as FemaleGender;
use TG\Domain\Gender\Pure\Male as MaleGender;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Female;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\WhatIsYourGenderOptionName;

class FromWhatIsYourGenderQuestionOptionName extends PreferenceId
{
    private $whatIsYourGenderOptionName;
    private $cached;

    public function __construct(WhatIsYourGenderOptionName $whatIsYourGenderOptionName)
    {
        $this->whatIsYourGenderOptionName = $whatIsYourGenderOptionName;
        $this->cached = null;
    }

    public function value(): int
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): int
    {
        switch ($this->whatIsYourGenderOptionName->value()) {
            case (new Male())->value():
                return (new MaleGender())->value();

            case (new Female())->value():
                return (new FemaleGender())->value();
        }

        throw new Exception(sprintf('Unknown preference %s', $this->whatIsYourGenderOptionName->value()));
    }
}