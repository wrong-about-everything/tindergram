<?php

declare(strict_types=1);

namespace TG\Domain\TelegramUser\UserId;

use TG\Domain\TelegramUser\TelegramUser;

class FromTelegramUser extends TelegramUserId
{
    private $user;

    public function __construct(TelegramUser $user)
    {
        $this->user = $user;
    }

    public function value(): string
    {
        return $this->user->value()->pure()->raw()['id'];
    }
}