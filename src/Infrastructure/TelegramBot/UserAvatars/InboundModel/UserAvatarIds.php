<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface UserAvatarIds
{
    public function value(): ImpureValue/*array*/;
}