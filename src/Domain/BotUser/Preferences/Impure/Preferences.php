<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preferences\Impure;

use TG\Domain\BotUser\Preferences\Pure\FromArray;
use TG\Domain\BotUser\Preferences\Pure\Intersected;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Preferences
{
    abstract public function value(): ImpureValue;

    final public function overlapped(Preferences $with): Preferences
    {
        if (!$this->value()->isSuccessful()) {
            return new NonSuccessful($this->value());
        }
        if (!$with->value()->isSuccessful()) {
            return new NonSuccessful($with->value());
        }

        return
            new FromPure(
                new Intersected(
                    new FromArray($this->value()->pure()->isPresent() ? $this->value()->pure()->raw() : []),
                    new FromArray($with->value()->pure()->isPresent() ? $with->value()->pure()->raw() : [])
                )
            );
    }
}