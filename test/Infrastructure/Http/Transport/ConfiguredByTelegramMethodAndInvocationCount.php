<?php

declare(strict_types = 1);

namespace TG\Tests\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\EagerlyInvoked;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\TelegramBot\Method\FromUrl;

class ConfiguredByTelegramMethodAndInvocationCount implements FakeTransport
{
    private $config;
    private $totalRequests;
    private $requestsPerMethod;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->totalRequests = [];
        $this->requestsPerMethod = [];
    }

    public function response(Request $request): Response
    {
        $eagerlyInvoked = new EagerlyInvoked($request);
        $this->totalRequests[] = $eagerlyInvoked;
        $this->requestsPerMethod[(new FromUrl($request->url()))->value()][] = $eagerlyInvoked;

        return $this->config[(new FromUrl($request->url()))->value()][count($this->requestsPerMethod[(new FromUrl($request->url()))->value()]) - 1];
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->totalRequests;
    }
}
