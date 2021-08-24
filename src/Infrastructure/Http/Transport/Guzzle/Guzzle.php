<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Transport\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Inbound\FromPsrResponse;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Response\Inbound\Unavailable;
use TG\Infrastructure\Http\Transport\HttpTransport;
use Throwable;

abstract class Guzzle implements HttpTransport
{
    public function response(Request $request): Response
    {
        try {
            return
                new FromPsrResponse(
                    $this->client()
                        ->request(
                            $request->method()->value(),
                            $request->url()->value(),
                            [
                                RequestOptions::HEADERS => $request->headers(),
                                RequestOptions::BODY => $request->body()
                            ]
                                +
                            $this->options()
                        )
                );
        } catch (Throwable $e) {
            return new Unavailable();
        }
    }

    abstract protected function client(): ClientInterface;

    abstract protected function options(): array;
}
