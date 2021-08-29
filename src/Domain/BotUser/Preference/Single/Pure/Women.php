<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Single\Pure;

class Women extends PreferenceId
{
    public function value(): int
    {
        return 1;
    }
}