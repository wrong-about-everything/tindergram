<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Routing;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\Route;

class FoundWithParams implements Route
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function matchResult(Request $request): MatchResult
    {
        return new Match($this->params);
    }
}