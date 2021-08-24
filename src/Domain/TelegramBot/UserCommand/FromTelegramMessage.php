<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\UserCommand;

use TG\Domain\TelegramBot\AvailableTelegramBotCommands;
use TG\Infrastructure\TelegramBot\UserCommand\FromTelegramMessage as UserCommandFromMessage;
use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

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