<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot;

use TG\Domain\Bot\BotToken\Pure\BotToken;
use TG\Infrastructure\TelegramBot\Method\Method;
use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Query;

class BotApiUrl extends Url
{
    private $method;
    private $query;
    private $botToken;

    public function __construct(Method $method, Query $query, BotToken $botToken)
    {
        $this->method = $method;
        $this->query = $query;
        $this->botToken = $botToken;
    }

    public function value(): string
    {
        return
            sprintf(
                'https://api.telegram.org/bot%s/%s?%s',
                $this->botToken->value(),
                $this->method->value(),
                $this->query->value()
            );
    }
}