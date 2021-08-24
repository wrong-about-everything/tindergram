<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\Severity;

use TG\Infrastructure\Logging\Severity;

class Error implements Severity
{
    public function value(): string
    {
        return 'error';
    }
}
