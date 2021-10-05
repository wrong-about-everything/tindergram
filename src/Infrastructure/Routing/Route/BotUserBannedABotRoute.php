<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;

class BotUserBannedABotRoute implements Route
{
    public function matchResult(Request $request): MatchResult
    {
        if (!$request->method()->equals(new Post())) {
            return new NotMatch();
        }
        $parsedBody = json_decode($request->body(), true);
        if (!isset($parsedBody['my_chat_member']['new_chat_member']['status']) || $parsedBody['my_chat_member']['new_chat_member']['status'] !== 'kicked') {
            return new NotMatch();
        }

        return
            new Match(
                [new FromInteger($parsedBody['my_chat_member']['from']['id'])]
            );
    }
}
