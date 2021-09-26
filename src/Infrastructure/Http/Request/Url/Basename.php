<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Url;

interface Basename
{
    public function value(): string;
}
