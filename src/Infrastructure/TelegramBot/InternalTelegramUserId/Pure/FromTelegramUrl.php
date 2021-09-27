<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;

class FromTelegramUrl extends InternalTelegramUserId
{
    private $concrete;

    public function __construct(Url $url)
    {
        $this->concrete = new FromInteger((int) ((new FromQuery(new FromUrl($url)))->value()['chat_id'] ?? (new FromQuery(new FromUrl($url)))->value()['user_id']));
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}