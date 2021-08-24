<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\ImpureValue;

use Exception;
use TG\Infrastructure\ImpureInteractions\Error;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\PureValue;

class Successful implements ImpureValue
{
    private $pureValue;

    public function __construct(PureValue $pureValue)
    {
        $this->pureValue = $pureValue;
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function pure(): PureValue
    {
        return $this->pureValue;
    }

    public function error(): Error
    {
        throw new Exception('Successful impure value does not have an error.');
    }
}