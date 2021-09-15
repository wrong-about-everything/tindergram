<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class AllAvatarsExcluding implements UserAvatarIds
{
    private $userAvatars;
    private $excludedAvatarIndex;

    public function __construct(UserAvatarIds $userAvatars, int $excludedAvatarIndex)
    {
        $this->userAvatars = $userAvatars;
        $this->excludedAvatarIndex = $excludedAvatarIndex;
    }

    public function value(): ImpureValue/*array*/
    {
        $userAvatars = $this->userAvatars->value();
        if (!$userAvatars->isSuccessful()) {
            return $userAvatars;
        }

        $pureAvatars = $userAvatars->pure()->raw();
        unset($pureAvatars[$this->excludedAvatarIndex]);

        return new Successful(new Present(array_values($pureAvatars)));
    }
}