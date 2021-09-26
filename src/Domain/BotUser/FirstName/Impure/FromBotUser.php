<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\FirstName\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromBotUser extends FirstName
{
    private $botUser;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
    }

    public function value(): ImpureValue
    {
        if (!$this->botUser->value()->isSuccessful()) {
            return $this->botUser->value();
        }

        return new Successful(new Present($this->botUser->value()->pure()->raw()['first_name']));
    }
}