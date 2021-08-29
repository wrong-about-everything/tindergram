<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromBotUser extends PreferenceIds
{
    private $botUser;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
    }

    public function value(): ImpureValue
    {
        if (!$this->botUser->value()->isSuccessful() || !$this->botUser->value()->pure()->isPresent()) {
            return $this->botUser->value();
        }

        return
            new Successful(
                is_null($this->botUser->value()->pure()->raw()['preferences'])
                    ? new Emptie()
                    :
                        new Present(
                            json_decode(
                                $this->botUser->value()->pure()->raw()['preferences'],
                                true
                            )
                        )
            );
    }
}