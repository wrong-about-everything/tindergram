<?php

declare(strict_types = 1);

namespace TG\Tests\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Transport\HttpTransport;

class Sleepy implements FakeTransport
{
    private $original;
    private $milliseconds;
    private $requests;

    public function __construct(HttpTransport $original, int $milliseconds)
    {
        $this->original = $original;
        $this->milliseconds = $milliseconds;
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->requests[] = $request;

        usleep($this->milliseconds * 1000);

        return $this->original->response($request);
    }

    public function sentRequests(): array
    {
        return $this->requests;
    }
}