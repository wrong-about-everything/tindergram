<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\FirstName\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class FirstName
{
    abstract public function value(): ImpureValue;
}