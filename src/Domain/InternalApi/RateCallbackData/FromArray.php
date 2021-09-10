<?php

declare(strict_types=1);

namespace TG\Domain\InternalApi\RateCallbackData;

use Exception;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\FromInteger;
use TG\Domain\TelegramBot\InlineAction\InlineActionType\Rating;

class FromArray extends RateCallbackData
{
    private $data;

    public function __construct(array $data)
    {
        if (!isset($data['type']) || !(new FromInteger($data['type']))->equals(new Rating())) {
            throw new Exception('This class can be used only with rate callback data');
        }
        if (!isset($data['action']) || !isset($data['pair_telegram_id'])) {
            throw new Exception('Some mandatory keys are absent');
        }

        $this->data = $data;
    }

    public function value(): array
    {
        return $this->data;
    }
}