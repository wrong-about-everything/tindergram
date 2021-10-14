<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Http\Response\Inbound;

use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Code\ResourceIsForbidden as ResourceIsForbiddenCode;
use TG\Infrastructure\Http\Response\Inbound\Response;

class ResourceIsForbidden implements Response
{
    public function code(): Code
    {
        return new ResourceIsForbiddenCode();
    }

    public function headers(): array
    {
        return [];
    }

    public function body(): string
    {
        return '';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}