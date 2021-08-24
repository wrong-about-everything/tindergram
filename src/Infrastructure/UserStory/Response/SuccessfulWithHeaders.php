<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\UserStory\Body;
use TG\Infrastructure\UserStory\Code;
use TG\Infrastructure\UserStory\Code\Successful as SuccessfulCode;
use TG\Infrastructure\UserStory\Response;

class SuccessfulWithHeaders implements Response
{
    private $body;
    private $headers;

    public function __construct(Body $body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function code(): Code
    {
        return new SuccessfulCode();
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): PureValue
    {
        return $this->body->value();
    }
}