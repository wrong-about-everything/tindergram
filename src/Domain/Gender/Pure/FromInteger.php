<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

class FromInteger extends Gender
{
    private $concrete;

    public function __construct(int $gender)
    {
        $this->concrete = $this->concrete($gender);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $gender): Gender
    {
        return [
            (new Male())->value() => new Male(),
            (new Female())->value() => new Female(),
        ][$gender]
            ??
        new NonExistentWithValue($gender);
    }
}