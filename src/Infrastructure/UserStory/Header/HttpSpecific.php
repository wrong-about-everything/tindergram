<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Header;

use TG\Infrastructure\Http\Response\Header as HttpResponseHeader;
use TG\Infrastructure\UserStory\Header;

class HttpSpecific extends Header
{
    private $httpResponseHeader;

    public function __construct(HttpResponseHeader $httpResponseHeader)
    {
        $this->httpResponseHeader = $httpResponseHeader;
    }

    public function value(): string
    {
        return $this->httpResponseHeader->value();
    }

    public function isHttpSpecific(): bool
    {
        return true;
    }
}