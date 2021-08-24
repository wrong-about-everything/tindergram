<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\MatchResult;

use Exception;
use TG\Infrastructure\Routing\MatchResult;

class CombinedMatch implements MatchResult
{
    private $left;
    private $right;

    public function __construct(MatchResult $left, MatchResult $right)
    {
        if (!$left->matches() || !$right->matches()) {
            throw new Exception('Both match results must be matches.');
        }

        $this->left = $left;
        $this->right = $right;
    }

    public function matches(): bool
    {
        return true;
    }

    public function params(): array
    {
        return array_merge($this->left->params(), $this->right->params());
    }
}