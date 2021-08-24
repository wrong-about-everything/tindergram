<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Url\Path;

use TG\Infrastructure\Http\Request\Url\Path;

class FromString implements Path
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}