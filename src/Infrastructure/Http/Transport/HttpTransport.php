<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Inbound\Response;

interface HttpTransport
{
    public function response(Request $request): Response;
}