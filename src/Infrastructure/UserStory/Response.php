<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory;

use TG\Infrastructure\ImpureInteractions\PureValue;

interface Response
{
    public function isSuccessful(): bool;

    public function code(): Code;

    public function headers(): array;

    public function body(): PureValue;
}