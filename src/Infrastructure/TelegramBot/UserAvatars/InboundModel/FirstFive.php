<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FirstFive implements UserAvatarIds
{
    private $userAvatars;

    public function __construct(UserAvatarIds $userAvatars)
    {
        $this->userAvatars = $userAvatars;
    }

    public function value(): ImpureValue/*array*/
    {
        $userAvatars = $this->userAvatars->value();
        if (!$userAvatars->isSuccessful()) {
            return $userAvatars;
        }

        return
            new Successful(
                new Present(
                    array_slice(
                        $this->userAvatars->value()->pure()->raw(),
                        0,
                        5
                    )
                )
            );
    }
}