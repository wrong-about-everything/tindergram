<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

use TG\Infrastructure\Http\Request\Outbound\Request;

class FromOutboundTelegramRequest extends InternalTelegramUserId
{
    private $concrete;

    public function __construct(Request $request)
    {
        $this->concrete = new FromTelegramUrl($request->url());
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