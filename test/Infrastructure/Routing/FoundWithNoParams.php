<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Routing;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\MatchWithNoParams;
use TG\Infrastructure\Routing\Route;

class FoundWithNoParams implements Route
{
    public function matchResult(Request $request): MatchResult
    {
        return new MatchWithNoParams();
    }
}