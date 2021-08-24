<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Host
{
    public function value(): string;
}
