<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Transport\HttpTransport;

interface FakeTransport extends HttpTransport
{
    /**
     * @return Request[]
     */
    public function sentRequests(): array;
}