<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging;

interface LogItem
{
    public function value(): array;
}
