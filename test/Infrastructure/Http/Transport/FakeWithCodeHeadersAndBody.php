<?php

declare(strict_types = 1);

namespace TG\Tests\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Infrastructure\Http\Transport\HttpTransport;

class FakeWithCodeHeadersAndBody implements HttpTransport
{
    private $code;
    private $headers;
    private $body;

    private $responses;
    private $requests;

    public function __construct(Code $code, array $headers, string $body)
    {
        $this->code = $code;
        $this->headers = $headers;
        $this->body = $body;
        $this->responses = [];
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $this->responses[] = new DefaultResponse($this->code, $this->headers, $this->body);
        $this->requests[] = $request;

        return $this->responses[sizeof($this->responses) - 1];
    }

    /**
     * @return Response[]
     */
    public function receivedResponses(): array
    {
        return $this->responses;
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
