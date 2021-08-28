<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\Method;

class GetUserProfilePhotos extends Method
{
    public function value(): string
    {
        return 'getUserProfilePhotos';
    }
}