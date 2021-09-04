<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

use Exception;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men as Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Women as Women;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\WhatDoYouPreferOptionName;

class FromWhatDoYouPreferQuestionOptionName extends Gender
{
    private $whatDoYouPreferOptionName;
    private $cached;

    public function __construct(WhatDoYouPreferOptionName $whatDoYouPreferOptionName)
    {
        $this->whatDoYouPreferOptionName = $whatDoYouPreferOptionName;
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

    private function concrete(): Gender
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): Gender
    {
        switch ($this->whatDoYouPreferOptionName->value()) {
            case (new Men())->value():
                return new Male();

            case (new Women())->value():
                return new Female();
        }

        throw new Exception(sprintf('Unknown option name %s', $this->whatDoYouPreferOptionName->value()));
    }
}