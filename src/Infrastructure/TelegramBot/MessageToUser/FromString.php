<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class FromString implements MessageToUser
{
    private $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function value(): string
    {
        return $this->text;
    }
}