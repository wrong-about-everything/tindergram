<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging;

interface Severity
{
    public function value(): string;
}
