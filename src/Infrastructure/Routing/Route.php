<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing;

use TG\Infrastructure\Http\Request\Inbound\Request;

interface Route
{
    public function matchResult(Request $httpRequest): MatchResult;
}
