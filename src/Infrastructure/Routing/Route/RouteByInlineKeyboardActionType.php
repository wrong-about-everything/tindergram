<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use Exception;
use TG\Domain\InternalApi\RateCallbackData\FromArray;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\FromParsedRequest;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\InlineActionType;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\Rating;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;

class RouteByInlineKeyboardActionType implements Route
{
    private $inlineActionType;

    public function __construct(InlineActionType $inlineActionType)
    {
        $this->inlineActionType = $inlineActionType;
    }

    public function matchResult(Request $request): MatchResult
    {
        if (!$request->method()->equals(new Post())) {
            return new NotMatch();
        }

        $parsedRequest = json_decode($request->body(), true);
        if (is_null($parsedRequest)) {
            return new NotMatch();
        }

        $actionType = new FromParsedRequest($parsedRequest);

        return
            $actionType->exists() && $actionType->equals($this->inlineActionType)
                ?
                    new Match([
                        new FromInteger($parsedRequest['callback_query']['from']['id']),
                        $this->callbackData($actionType, $parsedRequest)
                    ])
                : new NotMatch()
            ;
    }

    private function callbackData(InlineActionType $actionType, array $parsedRequest)
    {
        if ($actionType->equals(new Rating())) {
            return new FromArray(json_decode($parsedRequest['callback_query']['data'], true));
        }

        throw new Exception(sprintf('Forgot to handle action type %s', $actionType->value()));
    }
}
