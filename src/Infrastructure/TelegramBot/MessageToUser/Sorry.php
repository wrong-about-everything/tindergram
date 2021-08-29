<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class Sorry implements MessageToUser
{
    public function value(): string
    {
        return 'Простите, у нас что-то сломалось. Попробуйте ещё пару раз, и если не заработает — напишите, пожалуйста, в @hey_sweetie_support_bot';
    }
}