<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class YouHaveAMatch implements MessageToUser
{
    private $matchTelegramHandle;

    public function __construct(string $matchTelegramHandle)
    {
        $this->matchTelegramHandle = $matchTelegramHandle;
    }

    public function value(): string
    {
        return sprintf('Поздравляю, у вас новая пара — @%s! Почему бы вам не написать прямо сейчас? Можно начать просто с 👋', $this->matchTelegramHandle);
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}