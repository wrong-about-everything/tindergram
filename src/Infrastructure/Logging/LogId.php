<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging;

use TG\Infrastructure\Uuid\UUID;

class LogId
{
    private $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function value(): string
    {
        return $this->uuid->value();
    }
}
