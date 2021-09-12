<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class RegistrationCongratulations implements MessageToUser
{
    public function value(): string
    {
        return 'Поздравляю, вы зарегистрировались! Через пару дней начнём присылать профили. Если хотите что-то спросить или уточнить, смело пишите на @flurr_support_bot';
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}