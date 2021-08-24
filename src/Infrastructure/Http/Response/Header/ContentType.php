<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Header;

use TG\Infrastructure\Http\Response\Header;
use TG\Infrastructure\Http\Response\Header\ContentTypeValue\Value;

class ContentType extends Header
{
    private $value;

    public function __construct(Value $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return sprintf('Content-Type: %s', $this->value->value());
    }

    public function exists(): bool
    {
        return true;
    }
}