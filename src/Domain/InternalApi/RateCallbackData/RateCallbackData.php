<?php

declare(strict_types=1);

namespace TG\Domain\InternalApi\RateCallbackData;

abstract class RateCallbackData
{
    abstract public function value(): array;
}