<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class TeamManagement extends InterestId
{
    public function value(): int
    {
        return 14;
    }

    public function exists(): bool
    {
        return true;
    }
}