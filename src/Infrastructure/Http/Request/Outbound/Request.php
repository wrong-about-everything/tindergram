<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Outbound;

use TG\Infrastructure\Http\Request\Method;
use TG\Infrastructure\Http\Request\Url;

interface Request
{
    public function method(): Method;

    public function url(): Url;

    public function headers(): array/*Map<String, String> || Header[] || string[]*/;

    public function body(): string;
}