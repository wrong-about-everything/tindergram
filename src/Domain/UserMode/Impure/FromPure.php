<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Impure;

use TG\Domain\UserMode\Pure\Mode as PureMode;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Mode
{
    private $pureMode;

    public function __construct(PureMode $pureMode)
    {
        $this->pureMode = $pureMode;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureMode->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureMode->exists()));
    }
}