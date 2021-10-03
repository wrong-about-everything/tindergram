<?php

declare(strict_types=1);

namespace TG\Infrastructure\ABTesting\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class VariantId
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(VariantId $variantId): bool
    {
        return $this->value()->pure()->raw() === $variantId->value()->pure()->raw();
    }
}