<?php

declare(strict_types=1);

namespace TG\Infrastructure\ABTesting\Pure;

abstract class VariantId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(VariantId $variantId): bool
    {
        return $this->value() === $variantId->value();
    }
}