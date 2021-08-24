<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\MatchResult;

use TG\Infrastructure\Routing\MatchResult;

class MatchWithNoParams implements MatchResult
{
    public function matches(): bool
    {
        return true;
    }

    public function params(): array
    {
        return [];
    }
}