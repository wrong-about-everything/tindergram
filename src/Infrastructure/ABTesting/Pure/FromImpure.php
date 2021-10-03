<?php

declare(strict_types=1);

namespace TG\Infrastructure\ABTesting\Pure;

use TG\Infrastructure\ABTesting\Impure\VariantId as ImpureVariantId;

class FromImpure extends VariantId
{
    private $impureVariantId;

    public function __construct(ImpureVariantId $impureVariantId)
    {
        $this->impureVariantId = $impureVariantId;
    }

    public function value(): int
    {
        return $this->impureVariantId->value()->pure()->raw();
    }

    public function exists(): bool
    {
        return $this->impureVariantId->exists()->pure()->raw();
    }
}