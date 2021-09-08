<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single;

use Exception;

class WithCallbackData implements InlineKeyboardButton
{
    private $buttonText;
    private $callbackData;

    public function __construct(string $buttonText, array $callbackData)
    {
        if ($buttonText === '') {
            throw new Exception('Button text must be non-empty');
        }
        if (empty($callbackData)) {
            throw new Exception('Callback data must be non-empty');
        }

        $this->buttonText = $buttonText;
        $this->callbackData = $callbackData;
    }

    public function value(): array
    {
        return [
            'text' => $this->buttonText,
            'callback_data' => json_encode($this->callbackData)
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }
}