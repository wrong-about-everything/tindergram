<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Pure;

abstract class Status
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(Status $status): bool
    {
        return $this->value() === $status->value();
    }
}