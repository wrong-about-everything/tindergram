<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method;
use TG\Infrastructure\Http\Request\Url\Path\FromUrl;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\CombinedMatch;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\MatchResult\MatchWithNoParams;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;

class RouteByMethodAndPathPattern implements Route
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
        if (!$this->method->equals($httpRequest->method())) {
            return new NotMatch();
        }

        $parsedPath = $this->parsedPath($httpRequest);
        $parsedPathPattern = $this->parsedPathPattern();
        if (count($parsedPath) !== count($parsedPathPattern)) {
            return new NotMatch();
        }
        if ($parsedPath === $parsedPathPattern) {
            return new MatchWithNoParams();
        }

        return $this->firstMatchResultIteration($parsedPath, $parsedPathPattern);
    }

    private function parsedPath(Request $httpRequest)
    {
        return
            array_filter(
                explode('/', (new FromUrl($httpRequest->url()))->value()),
                function (string $part) {
                    return $part !== '';
                }
            );
    }

    private function parsedPathPattern()
    {
        return
            array_filter(
                explode('/', $this->pathPattern),
                function (string $part) {
                    return $part !== '';
                }
            );
    }

    private function firstMatchResultIteration(array $parsedPath, array $parsedPathPattern): MatchResult
    {
        return $this->nextMatchResultIteration(new MatchWithNoParams(), $parsedPath, $parsedPathPattern);
    }

    private function nextMatchResultIteration(MatchResult $matchResult, array $parsedPath, array $parsedPathPattern): MatchResult
    {
        if (empty($parsedPath) && empty($parsedPathPattern)) {
            return $matchResult;
        }

        $currentElementInParsedPath = array_shift($parsedPath);
        $currentElementInParsedPathPattern = array_shift($parsedPathPattern);
        if ($currentElementInParsedPathPattern[0] === ':') {
            return
                $this->nextMatchResultIteration(
                    new CombinedMatch($matchResult, new Match([$currentElementInParsedPath])),
                    $parsedPath,
                    $parsedPathPattern
                );
        }
        if ($currentElementInParsedPath === $currentElementInParsedPathPattern) {
            return $this->nextMatchResultIteration($matchResult, $parsedPath, $parsedPathPattern);
        }

        return new NotMatch();
    }
}
