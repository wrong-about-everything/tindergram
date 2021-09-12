<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface Pair
{
    public function value(): ImpureValue;
}