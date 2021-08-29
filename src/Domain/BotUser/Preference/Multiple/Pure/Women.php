<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Pure;

use TG\Domain\BotUser\Preference\Single\Pure\Women as WomenId;

class Women extends PreferenceIds
{
    public function value(): array
    {
        return [(new WomenId())->value()];
    }
}