<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class RegistrationCongratulations implements MessageToUser
{
    public function value(): string
    {
        return 'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @hey_sweetie_support_bot';
    }
}