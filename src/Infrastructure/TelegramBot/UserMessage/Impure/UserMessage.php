<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserMessage\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface UserMessage
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}