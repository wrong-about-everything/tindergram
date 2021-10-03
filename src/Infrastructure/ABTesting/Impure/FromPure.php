<?php

declare(strict_types=1);

namespace TG\Infrastructure\ABTesting\Impure;

use TG\Infrastructure\ABTesting\Pure\VariantId as PureVariantId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends VariantId
{
    private $pureVariantId;

    public function __construct(PureVariantId $pureVariantId)
    {
        $this->pureVariantId = $pureVariantId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureVariantId->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureVariantId->exists()));
    }
}