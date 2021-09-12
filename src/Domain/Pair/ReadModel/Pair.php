<?php

declare(strict_types=1);

namespace TG\Domain\Pair\ReadModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface Pair
{
    public function value(): ImpureValue;
}