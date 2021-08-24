<?php

declare(strict_types=1);

namespace RC\Domain\BotUser;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface BotUser
{
    public function value(): ImpureValue;
}