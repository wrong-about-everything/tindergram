<?php

declare(strict_types=1);

namespace TG\Domain\ABTesting\Pure;

use TG\Infrastructure\ABTesting\Pure\VariantId;

class SwitchToVisibleModeOnUpvote extends VariantId
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}