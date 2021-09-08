<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface ViewedPair
{
    public function value(): ImpureValue;
}