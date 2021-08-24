<?php

declare(strict_types=1);

namespace TG\Domain\RussianGrammar\RussianCase;

abstract class RussianCase
{
    abstract public function value(): int;

    final public function equals(RussianCase $case): bool
    {
        return $this->value() === $case->value();
    }
}