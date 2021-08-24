<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

class ProductCultureBuilding extends InterestId
{
    public function value(): int
    {
        return 10;
    }

    public function exists(): bool
    {
        return true;
    }
}