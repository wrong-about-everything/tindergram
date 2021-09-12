<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class ThatsAllForNow implements MessageToUser
{
    public function value(): string
    {
        return sprintf('На сегодня пока всё, а то вы такими темпами все лайки себе заберёте.');
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}