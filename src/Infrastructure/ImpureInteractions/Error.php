<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions;

abstract class Error
{
    abstract public function userMessage(): string;

    abstract public function severity(): Severity;

    abstract public function logMessage(): string;

    abstract public function context(): array;

    final public function equals(Error $error): bool
    {
        return
            $this->userMessage() === $error->userMessage()
                &&
            $this->severity()->equals($error->severity())
                &&
            $this->logMessage() === $error->logMessage()
                &&
            $this->context() === $error->context()
        ;
    }
}