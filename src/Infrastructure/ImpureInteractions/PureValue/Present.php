<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\PureValue;

use TG\Infrastructure\ImpureInteractions\PureValue;

class Present implements PureValue
{
    private $raw;

    public function __construct($raw)
    {
        $this->raw = $raw;
    }

    public function isPresent(): bool
    {
        return true;
    }

    public function raw()
    {
        return $this->raw;
    }
}