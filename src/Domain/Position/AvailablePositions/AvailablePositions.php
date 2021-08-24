<?php

declare(strict_types=1);

namespace RC\Domain\Position\AvailablePositions;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface AvailablePositions
{
    public function value(): ImpureValue;
}