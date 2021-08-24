<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\UserStory\Code;
use TG\Infrastructure\UserStory\Code\Unauthorized as UnauthorizedCode;
use TG\Infrastructure\UserStory\Response;

class Unauthorized implements Response
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function code(): Code
    {
        return new UnauthorizedCode();
    }

    public function headers(): array
    {
        return [];
    }

    public function body(): PureValue
    {
        return new Emptie();
    }
}