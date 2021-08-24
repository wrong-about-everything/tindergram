<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

class FromInteger extends Position
{
    private $concrete;

    public function __construct(int $position)
    {
        $this->concrete = isset($this->all()[$position]) ? $this->all()[$position] : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new ProductManager())->value() => new ProductManager(),
            (new SystemOrBusinessAnalyst())->value() => new SystemOrBusinessAnalyst(),
            (new ProductDesigner())->value() => new ProductDesigner(),
            (new CEO())->value() => new CEO(),
            (new ProjectManager())->value() => new ProjectManager(),
            (new ProductAnalyst())->value() => new ProductAnalyst(),
            (new Marketer())->value() => new Marketer(),
        ];
    }
}