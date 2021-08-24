<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Transport;

use GuzzleHttp\Client;
use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Infrastructure\Http\Request\Outbound\Request as OutboundRequest;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use TG\Infrastructure\Http\Transport\Guzzle\WithConnectTimeout;
use TG\Infrastructure\Http\Transport\Guzzle\WithTimeout;
use TG\Infrastructure\Logging\Logs;

class EnvironmentDependentTransport implements HttpTransport
{
    private $transport;

    public function __construct(DirPath $rootDir, Logs $logs)
    {
        $this->transport = $this->concrete($rootDir, $logs);
    }

    public function response(OutboundRequest $request): Response
    {
        return $this->transport->response($request);
    }

    private function concrete(DirPath $root, Logs $logs): HttpTransport
    {
        if (
            (new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()
        ) {
            return new Indifferent();
        }

        return
            new WithLogging(
                new WithConnectTimeout(
                    new WithTimeout(
                        new DefaultGuzzle(new Client()),
                        1
                    ),
                    1
                ),
                $logs
            );
    }
}
