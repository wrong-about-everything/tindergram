<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\UserMode\Pure\FromBotUserDatabaseRecord;
use TG\Domain\UserMode\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends Mode
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

    private function concrete(): Mode
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Mode
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromBotUserDatabaseRecord($this->botUser->value()->pure()->raw()));
    }
}