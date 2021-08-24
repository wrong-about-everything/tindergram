<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url;

interface Scheme
{
    public function value(): string;

    public function isSpecified(): bool;
}
