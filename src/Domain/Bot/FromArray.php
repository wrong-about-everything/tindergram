<?php

declare(strict_types=1);

namespace TG\Domain\Bot;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromArray implements Bot
{
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->array));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present(true));
    }
}