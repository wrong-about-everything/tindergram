<?php

declare(strict_types=1);

namespace TG\Domain\Bot\Name\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface Name
{
    public function value(): ImpureValue;
}