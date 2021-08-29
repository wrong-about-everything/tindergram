<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Pure;

class Intersected extends PreferenceIds
{
    private $left;
    private $right;

    public function __construct(PreferenceIds $left, PreferenceIds $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function value(): array
    {
        return array_intersect($this->left->value(), $this->right->value());
    }
}