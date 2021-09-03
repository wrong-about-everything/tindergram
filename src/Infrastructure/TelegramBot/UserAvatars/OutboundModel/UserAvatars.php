<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface UserAvatars
{
    public function value(): ImpureValue;
}