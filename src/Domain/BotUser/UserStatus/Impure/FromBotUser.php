<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Impure;

use TG\Domain\BotUser\BotUser;
use TG\Domain\BotUser\UserStatus\Pure\FromInteger;
use TG\Domain\BotUser\UserStatus\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends UserStatus
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
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return
            isset($this->botUser->value()->pure()->raw()['status'])
                ? new FromPure(new FromInteger($this->botUser->value()->pure()->raw()['status']))
                : new FromPure(new NonExistent())
            ;
    }
}