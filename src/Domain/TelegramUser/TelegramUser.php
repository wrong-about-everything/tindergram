<?php

declare(strict_types=1);

namespace TG\Domain\TelegramUser;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface TelegramUser
{
    public function value(): ImpureValue;
}