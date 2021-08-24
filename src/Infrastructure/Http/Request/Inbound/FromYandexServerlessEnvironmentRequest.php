<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Inbound;

use TG\Infrastructure\Http\Request\Inbound\Composite as CompositeRequest;
use TG\Infrastructure\Http\Request\Method;
use TG\Infrastructure\Http\Request\Method\FromString as HttpMethodFromString;
use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Composite as CompositeUrl;
use TG\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use TG\Infrastructure\Http\Request\Url\Host\Localhost;
use TG\Infrastructure\Http\Request\Url\Path\FromString as PathFromString;
use TG\Infrastructure\Http\Request\Url\Port\NonSpecified as NonSpecifiedPort;
use TG\Infrastructure\Http\Request\Url\Query\FromArray as QueryFromArray;
use TG\Infrastructure\Http\Request\Url\Scheme\Https;

class FromYandexServerlessEnvironmentRequest implements Request
{
    private $concrete;

    public function __construct(array $message)
    {
        $this->concrete = $this->concrete($message);
    }

    public function method(): Method
    {
        return $this->concrete->method();
    }

    public function url(): Url
    {
        return $this->concrete->url();
    }

    public function headers(): array/*Map<String, String>*/
    {
        return $this->concrete->headers();
    }

    public function body(): string
    {
        return $this->concrete->body();
    }

    private function concrete(array $message): CompositeRequest
    {
        return
            new CompositeRequest(
                new HttpMethodFromString($message['httpMethod']),
                new CompositeUrl(
                    new Https(),
                    new Localhost(),
                    new NonSpecifiedPort(),
                    new PathFromString($message['queryStringParameters']['ad_hoc_path'] ?? ''),
                    new QueryFromArray($message['queryStringParameters']),
                    new NonSpecifiedFragment()
                ),
                $message['headers'],
                $message['body']
            );
    }
}