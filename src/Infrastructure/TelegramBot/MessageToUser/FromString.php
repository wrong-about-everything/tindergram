<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

use Exception;

class FromString implements MessageToUser
{
    private $text;

    public function __construct(string $text)
    {
        if ($text === '') {
            throw new Exception('Text can not be empty. If you want to create an empty message, use class Emptie instead.');
        }

        $this->text = $text;
    }

    public function value(): string
    {
        return $this->text;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}