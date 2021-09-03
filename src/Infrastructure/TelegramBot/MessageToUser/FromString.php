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
            throw new Exception('Text can not be empty');
        }

        $this->text = $text;
    }

    public function value(): string
    {
        return $this->text;
    }
}