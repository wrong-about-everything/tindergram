<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

interface MessageToUser
{
    public function value(): string;

    public function isNonEmpty(): bool;
}