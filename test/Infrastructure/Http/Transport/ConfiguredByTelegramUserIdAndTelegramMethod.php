<?php

declare(strict_types = 1);

namespace TG\Tests\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\EagerlyInvoked;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Request\Url\Basename\FromUrl as BasenameFromUrl;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromOutboundTelegramRequest;

class ConfiguredByTelegramUserIdAndTelegramMethod implements FakeTransport
{
    private $config;
    private $requests;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $eagerlyInvoked = new EagerlyInvoked($request);
        $this->requests[] = $eagerlyInvoked;
        return $this->config[(new FromOutboundTelegramRequest($request))->value()][(new BasenameFromUrl($request->url()))->value()];
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
