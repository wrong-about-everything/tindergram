<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Body;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\UserStory\Body;

class Arrray extends Body
{
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function value(): PureValue
    {
        return new Present($this->payload);
    }
}