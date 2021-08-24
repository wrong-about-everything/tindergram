<?php

declare(strict_types=1);

namespace TG\Infrastructure\ExecutionEnvironmentAdapter;

interface RawPhpFpmWebService
{
    public function response(): void;
}