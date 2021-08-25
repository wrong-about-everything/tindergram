<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot;

use TG\Infrastructure\TelegramBot\Method\Method;
use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Query;

class BotApiUrl extends Url
{
    private $method;
    private $query;

    public function __construct(Method $method, Query $query)
    {
        $this->method = $method;
        $this->query = $query;
    }

    public function value(): string
    {
        return
            sprintf(
                'https://api.telegram.org/bot%s/%s?%s',
                getenv('BOT_TOKEN'),
                $this->method->value(),
                $this->query->value()
            );
    }
}