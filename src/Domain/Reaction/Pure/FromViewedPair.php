<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

use TG\Domain\ViewedPair\ReadModel\ViewedPair;

class FromViewedPair extends Reaction
{
    private $concrete;

    public function __construct(ViewedPair $viewedPair)
    {
        $this->concrete = $this->concrete($viewedPair);
    }

    function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(ViewedPair $viewedPair): Reaction
    {
        return new FromInteger($viewedPair->value()->pure()->raw()['reaction']);
    }
}