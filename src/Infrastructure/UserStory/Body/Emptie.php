<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Body;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie as EmptieValue;
use TG\Infrastructure\UserStory\Body;

class Emptie extends Body
{
    public function value(): PureValue
    {
        return new EmptieValue();
    }
}