<?php

declare(strict_types=1);

namespace TG\Domain\InternalApi\RateCallbackData;

use TG\Domain\TelegramBot\InlineAction\InlineActionType\Rating;
use TG\Domain\TelegramBot\InlineAction\ThumbsUp as ThumbsUpAction;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class ThumbsUp extends RateCallbackData
{
    private $pairTelegramId;

    public function __construct(InternalTelegramUserId $pairTelegramId)
    {
        $this->pairTelegramId = $pairTelegramId;
    }

    public function value(): array
    {
        return [
            'type' => (new Rating())->value(),
            'action' => (new ThumbsUpAction())->value(),
            'pair_telegram_id' => $this->pairTelegramId->value(),
       ];
    }
}