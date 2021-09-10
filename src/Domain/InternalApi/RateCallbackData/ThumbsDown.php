<?php

declare(strict_types=1);

namespace TG\Domain\InternalApi\RateCallbackData;

use TG\Domain\TelegramBot\InlineAction\InlineActionType\Rating;
use TG\Domain\TelegramBot\InlineAction\ThumbsDown as ThumbsDownAction;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class ThumbsDown extends RateCallbackData
{
    private $pairTelegramId;

    public function __construct(InternalTelegramUserId $pairTelegramId)
    {
        $this->pairTelegramId = $pairTelegramId;
    }

    public function value(): array
    {
        return [
            'action' => (new ThumbsDownAction())->value(),
            'pair_telegram_id' => $this->pairTelegramId->value(),
            'type' => (new Rating())->value(),
        ];
    }
}