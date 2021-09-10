<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair\ReadModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface ViewedPair
{
    public function value(): ImpureValue;
}