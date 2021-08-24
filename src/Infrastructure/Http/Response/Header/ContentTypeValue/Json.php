<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Header\ContentTypeValue;

class Json extends Value
{
    public function value(): string
    {
        return 'application/json;charset=UTF-8';
    }
}