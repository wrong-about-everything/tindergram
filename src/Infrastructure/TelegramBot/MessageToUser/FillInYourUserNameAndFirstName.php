<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class FillInYourUserNameAndFirstName implements MessageToUser
{
    public function value(): string
    {
        return 'Hey sweetie, у тебя не установлен ник! Как установишь, снова жми /start.';
    }
}