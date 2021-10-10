<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\UserCommand;

use TG\Infrastructure\TelegramBot\UserCommand\UserCommand;

class ProfileSettings extends UserCommand
{
    public function value(): string
    {
        return 'profile_settings';
    }

    public function exists(): bool
    {
        // TODO: Implement exists() method.
    }
}