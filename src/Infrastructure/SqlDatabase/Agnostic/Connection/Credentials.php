<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Connection;

interface Credentials
{
    public function username(): string;

    public function password(): string;
}
