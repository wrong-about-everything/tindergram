<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface BotUser
{
    public function value(): ImpureValue;
}