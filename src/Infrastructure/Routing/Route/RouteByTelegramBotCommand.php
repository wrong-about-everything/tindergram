<?php

declare(strict_types=1);

namespace TG\Infrastructure\Routing\Route;

use TG\Domain\TelegramBot\UserCommand\FromTelegramMessage;
use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Routing\MatchResult;
use TG\Infrastructure\Routing\MatchResult\Match;
use TG\Infrastructure\Routing\MatchResult\NotMatch;
use TG\Infrastructure\Routing\Route;
use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

class RouteByTelegramBotCommand implements Route
{
    private $command;

    public function __construct(UserCommand $command)
    {
        $this->command = $command;
    }

    public function matchResult(Request $httpRequest): MatchResult
    {
        if (!$httpRequest->method()->equals(new Post())) {
            return new NotMatch();
        }

        $userCommand = new FromTelegramMessage($httpRequest->body());

        return
            $userCommand->exists() && $userCommand->equals($this->command)
                ?
                    new Match(
                        [json_decode($httpRequest->body(), true)]
                    )
                : new NotMatch()
            ;
    }
}
