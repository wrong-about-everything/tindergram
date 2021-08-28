<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Impure;

use TG\Domain\Gender\Pure\Gender as PureGender;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Gender
{
    private $pureGender;

    public function __construct(PureGender $pureGender)
    {
        $this->pureGender = $pureGender;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureGender->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureGender->exists()));
    }
}