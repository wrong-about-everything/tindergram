<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\ImpureValue;

use Exception;
use TG\Infrastructure\ImpureInteractions\Error;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\PureValue;

class Failed implements ImpureValue
{
    private $error;

    public function __construct(Error $error)
    {
        $this->error = $error;
    }

    public function isSuccessful(): bool
    {
        return false;
    }

    public function pure(): PureValue
    {
        throw new Exception('Failed impure value does not have a value.');
    }

    public function error(): Error
    {
        return $this->error;
    }
}