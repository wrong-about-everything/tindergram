<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory;

abstract class Code
{
    abstract public function value(): int;

    final public function equals(Code $code): bool
    {
        return $this->value() === $code->value();
    }
}