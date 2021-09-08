<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class FillInYourUserNameAndFirstName implements MessageToUser
{
    public function value(): string
    {
        return 'У вас не установлен ник в telegram. Установите и снова нажмите /start.';
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}