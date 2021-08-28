<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\SentReplyToUser;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface SentReplyToUser
{
    public function value(): ImpureValue;
}