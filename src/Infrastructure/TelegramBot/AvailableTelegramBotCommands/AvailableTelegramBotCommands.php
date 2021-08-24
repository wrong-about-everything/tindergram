<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\AvailableTelegramBotCommands;

use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

interface AvailableTelegramBotCommands
{
    public function contain(string $commandName): bool;

    public function get(string $commandName): UserCommand;
}