<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\EagerlyInvoked;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Tests\Infrastructure\Http\Transport\FakeTransport;

class Indifferent implements FakeTransport
{
    private $requests;

    public function __construct()
    {
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->requests[] = new EagerlyInvoked($request);
        return new DefaultResponse(new Ok(), [], '{"ok":true}');
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
