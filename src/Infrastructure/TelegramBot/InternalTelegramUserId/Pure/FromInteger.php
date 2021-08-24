<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure;

class FromInteger extends InternalTelegramUserId
{
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function value(): int
    {
        return $this->id;
    }

    public function exists(): bool
    {
        return true;
    }
}