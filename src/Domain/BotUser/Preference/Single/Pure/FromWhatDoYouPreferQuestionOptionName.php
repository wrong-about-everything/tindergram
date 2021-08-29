<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Single\Pure;

use Exception;
use TG\Domain\BotUser\Preference\Single\Pure\Men as MenPreferenceId;
use TG\Domain\BotUser\Preference\Single\Pure\Women as WomenPreferenceId;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men as Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Women as Women;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\WhatDoYouPreferOptionName;

class FromWhatDoYouPreferQuestionOptionName extends PreferenceId
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
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): int
    {
        switch ($this->whatDoYouPreferOptionName->value()) {
            case (new Men())->value():
                return (new MenPreferenceId())->value();

            case (new Women())->value():
                return (new WomenPreferenceId())->value();
        }

        throw new Exception(sprintf('Unknown preference %s', $this->whatDoYouPreferOptionName->value()));
    }
}