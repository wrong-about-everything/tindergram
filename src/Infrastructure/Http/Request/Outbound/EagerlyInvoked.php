<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Outbound;

use TG\Infrastructure\Http\Request\Method;
use TG\Infrastructure\Http\Request\Method\FromString as HttpMethod;
use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\FromString;

class EagerlyInvoked implements Request
{
    private $method;
    private $url;
    private $headers;
    private $body;

    public function __construct(Request $request)
    {
        $this->method = new HttpMethod($request->method()->value());
        $this->url = new FromString($request->url()->value());
        $this->headers = [];
        $this->body = $request->body();
    }

    public function method(): Method
    {
        return $this->method;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }
}