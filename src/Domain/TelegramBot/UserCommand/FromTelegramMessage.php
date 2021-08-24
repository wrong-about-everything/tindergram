<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\UserCommand;

use RC\Domain\TelegramBot\AvailableTelegramBotCommands;
use RC\Infrastructure\TelegramBot\UserCommand\FromTelegramMessage as UserCommandFromMessage;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

class FromTelegramMessage extends UserCommand
{
    private $concrete;

    public function __construct(string $telegramMessage)
    {
        $this->concrete = new UserCommandFromMessage($telegramMessage, new AvailableTelegramBotCommands());
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }
}