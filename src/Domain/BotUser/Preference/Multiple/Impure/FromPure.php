<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Impure;

use TG\Domain\BotUser\Preference\Multiple\Pure\PreferenceIds as PurePreferences;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends PreferenceIds
{
    private $purePreferences;

    public function __construct(PurePreferences $purePreferences)
    {
        $this->purePreferences = $purePreferences;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->purePreferences));
    }
}