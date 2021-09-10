<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class YouCanNotRateAUserMoreThanOnce implements MessageToUser
{
    public function value(): string
    {
        return
            <<<text
Пользователя можно оценить только один раз, и вы уже сделали это ранее.
Если хотите что-то спросить или уточнить, смело пишите на @flurr_support_bot
text
            ;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}