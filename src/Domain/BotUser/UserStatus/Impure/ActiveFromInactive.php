<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\UserStatus\Pure\InactiveBeforeRegistered;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class ActiveFromInactive extends UserStatus
{
    private $botUser;
    private $cached;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): UserStatus
    {
        return
            (new FromBotUser($this->botUser))->equals(new FromPure(new InactiveBeforeRegistered()))
                ? new FromPure(new RegistrationIsInProgress())
                : new FromPure(new Registered())
            ;
    }
}