<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserId;

use TG\Domain\BotUser\WriteModel\BotUser;

class FromWriteModelBotUser extends BotUserId
{
    private $user;

    public function __construct(BotUser $user)
    {
        $this->user = $user;
    }

    public function value(): string
    {
        return $this->user->value()->pure()->raw();
    }
}