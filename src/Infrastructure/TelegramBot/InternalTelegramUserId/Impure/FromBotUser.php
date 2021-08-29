<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;

class FromBotUser extends InternalTelegramUserId
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

    private function concrete(): InternalTelegramUserId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): InternalTelegramUserId
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }

        return new FromPure(new FromInteger($this->botUser->value()->pure()->raw()['telegram_id']));
    }
}