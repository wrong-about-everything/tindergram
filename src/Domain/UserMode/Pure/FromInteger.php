<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

class FromInteger extends Mode
{
    private $concrete;

    public function __construct(int $mode)
    {
        $this->concrete = $this->concrete($mode);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $mode): Mode
    {
        return [
            (new Visible())->value() => new Visible(),
            (new Invisible())->value() => new Invisible(),
        ][$mode] ?? new NonExistent();
    }
}