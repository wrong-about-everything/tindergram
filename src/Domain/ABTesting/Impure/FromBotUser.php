<?php

declare(strict_types=1);

namespace TG\Domain\ABTesting\Impure;

use TG\Domain\ABTesting\Pure\FromBotUserArray;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\ABTesting\Impure\FromPure;
use TG\Infrastructure\ABTesting\Impure\NonSuccessful;
use TG\Infrastructure\ABTesting\Impure\VariantId;
use TG\Infrastructure\ABTesting\Pure\NonExistent;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class FromBotUser extends VariantId
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

    private function concrete(): VariantId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): VariantId
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromBotUserArray($this->botUser->value()->pure()->raw()));
    }
}