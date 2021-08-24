<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\Severity;

use TG\Infrastructure\ImpureInteractions\Severity;

class Info extends Severity
{
    public function value(): int
    {
        return 0;
    }
}