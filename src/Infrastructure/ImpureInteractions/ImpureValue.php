<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions;

interface ImpureValue
{
    public function isSuccessful(): bool;

    public function pure(): PureValue;

    public function error(): Error;
}