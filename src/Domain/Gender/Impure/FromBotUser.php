<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\Gender\Pure\FromInteger;
use TG\Domain\Gender\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends Gender
{
    private $botUser;
    private $concrete;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): Gender
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): Gender
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent() || is_null($this->botUser->value()->pure()->raw()['gender'])) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->botUser->value()->pure()->raw()['gender']));
    }
}