<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Impure;

use TG\Domain\BotUser\Preference\Multiple\Pure\FromArray;
use TG\Domain\BotUser\Preference\Multiple\Pure\Intersected;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class PreferenceIds
{
    abstract public function value(): ImpureValue;

    final public function overlapped(PreferenceIds $with): PreferenceIds
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