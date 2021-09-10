<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Routing;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;

class NotFound implements Route
{
    public function matchResult(Request $request): MatchResult
    {
        return new NotMatch();
    }
}