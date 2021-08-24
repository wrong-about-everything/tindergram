<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl as QueryFromUrl;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\CombinedMatch;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\Route;

class RouteByMethodAndPathPatternWithQuery implements Route
{
    private $method;
    private $pathPattern;

    public function __construct(Method $method, string $pathPattern)
    {
        $this->method = $method;
        $this->pathPattern = $pathPattern;
    }

    public function matchResult(Request $httpRequest): MatchResult
    {
        $matchResult = (new RouteByMethodAndPathPattern($this->method, $this->pathPattern))->matchResult($httpRequest);
        if (!$matchResult->matches()) {
            return $matchResult;
        }

        return new CombinedMatch($matchResult, new Match([new QueryFromUrl($httpRequest->url())]));
    }
}
