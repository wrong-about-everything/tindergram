<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing;

interface MatchResult
{
    public function matches(): bool;

    public function params(): array;
}