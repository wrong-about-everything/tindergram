<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InternalTelegramUserId\Impure;

use TG\Domain\BotUser\WriteModel\BotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\FromPure;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\NonSuccessful;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\NonExistent;

class FromWriteModelBotUser extends InternalTelegramUserId
{
    private $botUser;
    private $userTelegramId;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
        $this->userTelegramId = null;
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
        if (is_null($this->userTelegramId)) {
            $this->userTelegramId = $this->doConcrete();
        }

        return $this->userTelegramId;
    }

    private function doConcrete(): InternalTelegramUserId
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return new NonSuccessful($this->botUser->value());
        }
        if (!$this->botUser->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->botUser->value()->pure()->raw()));
    }
}