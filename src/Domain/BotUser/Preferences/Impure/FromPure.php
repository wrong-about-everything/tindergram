<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preferences\Impure;

use TG\Domain\BotUser\Preferences\Pure\Preferences as PurePreferences;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Preferences
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