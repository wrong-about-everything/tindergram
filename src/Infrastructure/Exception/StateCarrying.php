<?php

declare(strict_types=1);

namespace TG\Infrastructure\Exception;

use Exception;
use TG\Infrastructure\ImpureInteractions\Error;

/**
 * Just in case:
 * Exceptions are bad for flow control.
 * This class is not intended for it. It is created ONLY for carrying the original context when short-circuiting took place.
 * For example, see `TransactionalQueryFromMultipleQueries` class.
 */
class StateCarrying extends Exception
{
    private $error;

    public function __construct(Error $error)
    {
        parent::__construct($error->logMessage());
        $this->error = $error;
    }

    public function error(): Error
    {
        return $this->error;
    }
}