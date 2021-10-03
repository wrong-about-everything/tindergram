<?php

declare(strict_types=1);

namespace TG\Domain\ABTesting\Pure;

use TG\Infrastructure\ABTesting\Pure\NonExistent;
use TG\Infrastructure\ABTesting\Pure\VariantId;

class FromInteger extends VariantId
{
    private $concrete;

    public function __construct(int $value)
    {
        $this->concrete = $this->concrete($value);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $value): VariantId
    {
        return [
            (new SwitchToVisibleModeOnUpvote())->value() => new SwitchToVisibleModeOnUpvote(),
            (new SwitchToVisibleModeOnRequest())->value() => new SwitchToVisibleModeOnRequest(),
        ][$value] ?? new NonExistent();
    }
}