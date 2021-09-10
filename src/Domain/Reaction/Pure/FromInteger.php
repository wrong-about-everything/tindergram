<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

class FromInteger extends Reaction
{
    private $concrete;

    public function __construct(int $reaction)
    {
        $this->concrete = $this->concrete($reaction);
    }

    function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $reaction): Reaction
    {
        return [
            (new Like())->value() => new Like(),
            (new Dislike())->value() => new Dislike(),
        ][$reaction]
            ??
        new NonExistent();
    }
}