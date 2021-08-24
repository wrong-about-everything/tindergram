<?php

declare(strict_types=1);

namespace TG\Infrastructure\Uuid;

interface UUID
{
    public function value(): string;
}