<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Pure;

use TG\Domain\BotUser\Preference\Single\Pure\Men as MenId;

class Men extends PreferenceIds
{
    public function value(): array
    {
        return [(new MenId())->value()];
    }
}