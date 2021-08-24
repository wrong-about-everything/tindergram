<?php

declare(strict_types=1);

namespace TG\Domain\TelegramUser\UserId;

use TG\Infrastructure\Uuid\UUID;

class FromUuid extends TelegramUserId
{
    private $userId;

    public function __construct(UUID $botId)
    {
        $this->userId = $botId;
    }

    public function value(): string
    {
        return $this->userId->value();
    }
}