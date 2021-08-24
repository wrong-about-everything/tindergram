<?php

declare(strict_types=1);

namespace TG\Domain\Bot\BotToken\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class BotToken
{
    abstract public function value(): ImpureValue;

    final public function equals(BotToken $botToken): bool
    {
        return $this->value()->pure()->raw() === $botToken->value()->pure()->raw();
    }
}