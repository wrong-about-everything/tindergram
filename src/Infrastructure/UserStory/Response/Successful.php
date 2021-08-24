<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\UserStory\Body;
use TG\Infrastructure\UserStory\Code;
use TG\Infrastructure\UserStory\Code\Successful as SuccessfulCode;
use TG\Infrastructure\UserStory\Response;

class Successful implements Response
{
    private $body;

    public function __construct(Body $body)
    {
        $this->body = $body;
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
        return [];
    }

    public function body(): PureValue
    {
        return $this->body->value();
    }
}