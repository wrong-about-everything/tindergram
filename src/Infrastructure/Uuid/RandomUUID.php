<?php

declare(strict_types=1);

namespace TG\Infrastructure\Uuid;

use Ramsey\Uuid\Uuid as RamseyUuid;

class RandomUUID implements UUID
{
    private $value;

    public function __construct()
    {
        $this->value = RamseyUuid::uuid4()->toString();
    }

    public function value(): string
    {
        return $this->value;
    }
}
