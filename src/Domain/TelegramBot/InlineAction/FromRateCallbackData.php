<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;

class FromRateCallbackData extends InlineAction
{
    private $rateCallbackData;

    public function __construct(RateCallbackData $rateCallbackData)
    {
        $this->rateCallbackData = $this->concrete($rateCallbackData);
    }

    public function value(): int
    {
        return $this->rateCallbackData->value();
    }

    public function exists(): bool
    {
        return $this->rateCallbackData->exists();
    }

    private function concrete(RateCallbackData $rateCallbackData): InlineAction
    {
        return new FromInteger($rateCallbackData->value()['action']);
    }
}