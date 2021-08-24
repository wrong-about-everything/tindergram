<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\UserCommand;

use TG\Domain\TelegramBot\AvailableTelegramBotCommands;
use TG\Infrastructure\TelegramBot\UserCommand\FromString as UserCommandFromString;
use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

class FromString extends UserCommand
{
    private $concrete;

    public function __construct(string $commandName)
    {
        $this->concrete = new UserCommandFromString($commandName, new AvailableTelegramBotCommands());
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