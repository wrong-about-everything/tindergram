<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preferences\Pure;

class Intersected extends Preferences
{
    private $left;
    private $right;

    public function __construct(Preferences $left, Preferences $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function value(): array
    {
        return array_intersect($this->left->value(), $this->right->value());
    }
}