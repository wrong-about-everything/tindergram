<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\SentReplyToUser;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;

class Nothing implements MessageSentToUser
{
    public function value(): ImpureValue
    {
        return new Successful(new Emptie());
    }
}