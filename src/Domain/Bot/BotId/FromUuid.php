<?php

declare(strict_types=1);

namespace TG\Domain\Bot\BotId;

use TG\Infrastructure\Uuid\UUID;

class FromUuid extends BotId
{
    private $botId;

    public function __construct(UUID $botId)
    {
        $this->botId = $botId;
    }

    public function value(): string
    {
        return $this->botId->value();
    }

    public function exists(): bool
    {
        return true;
    }
}