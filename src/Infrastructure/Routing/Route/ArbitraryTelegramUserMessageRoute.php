<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromTelegramMessage;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;

class ArbitraryTelegramUserMessageRoute implements Route
{
    public function matchResult(Request $request): MatchResult
    {
        if (!$request->method()->equals(new Post())) {
            return new NotMatch();
        }

        $userMessage = new FromTelegramMessage($request->body());

        return
            $userMessage->exists()
                ?
                    new Match(
                        [json_decode($request->body(), true)]
                    )
                : new NotMatch()
            ;
    }
}
