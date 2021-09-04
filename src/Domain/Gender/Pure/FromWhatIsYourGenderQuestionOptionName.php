<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

use Exception;
use TG\Domain\Gender\Pure\Female as FemaleGender;
use TG\Domain\Gender\Pure\Male as MaleGender;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Female;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\WhatIsYourGenderOptionName;

class FromWhatIsYourGenderQuestionOptionName extends Gender
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
        return $this->concrete()->value();
    }

    public function exists(): bool
    {
        return $this->concrete()->exists();
    }

    private function concrete()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): Gender
    {
        switch ($this->whatIsYourGenderOptionName->value()) {
            case (new Male())->value():
                return new MaleGender();

            case (new Female())->value():
                return new FemaleGender();
        }

        throw new Exception(sprintf('Unknown option name %s', $this->whatIsYourGenderOptionName->value()));
    }
}