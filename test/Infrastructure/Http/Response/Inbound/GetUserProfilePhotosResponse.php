<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Http\Response\Inbound;

use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Infrastructure\Http\Response\Inbound\Response;

class GetUserProfilePhotosResponse implements Response
{
    private $concrete;

    public function __construct(int $nAvatars)
    {
        $this->concrete =
            new DefaultResponse(
                new Ok(),
                [],
                json_encode([
                    'result' => [
                        'photos' =>
                            array_map(
                                function (int $n) {
                                    return [
                                        ['file_id' => sprintf('file_%s', $n)]
                                    ];
                                },
                                range(1, $nAvatars)
                            )
                    ]
                ])
            );
    }

    public function code(): Code
    {
        return $this->concrete->code();
    }

    public function headers(): array
    {
        return $this->concrete->headers();
    }

    public function body(): string
    {
        return $this->concrete->body();
    }

    public function isAvailable(): bool
    {
        return $this->concrete->isAvailable();
    }
}