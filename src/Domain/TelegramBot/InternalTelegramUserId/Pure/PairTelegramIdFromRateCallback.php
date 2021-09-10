<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InternalTelegramUserId\Pure;

use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class PairTelegramIdFromRateCallback extends InternalTelegramUserId
{
    private $concrete;

    public function __construct(RateCallbackData $rateCallbackData)
    {
        $this->concrete = $this->concrete($rateCallbackData);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(RateCallbackData $rateCallbackData): InternalTelegramUserId
    {
        return new FromInteger($rateCallbackData->value()['pair_telegram_id']);
    }
}