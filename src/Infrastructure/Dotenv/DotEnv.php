<?php

declare(strict_types=1);

namespace TG\Infrastructure\Dotenv;

interface DotEnv
{
    public function load(): void;
}
