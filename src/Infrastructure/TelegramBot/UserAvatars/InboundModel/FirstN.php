<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

/**
 * This class doesn't seem to bring any value: getFile method might succeed on an absent avatar.
 */
class FirstN implements UserAvatarIds
{
    private $userAvatars;
    private $avatarsAmount;
    private $cached;

    public function __construct(UserAvatarIds $userAvatars, int $avatarsAmount)
    {
        $this->userAvatars = $userAvatars;
        $this->avatarsAmount = $avatarsAmount;
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
                    array_slice($userAvatars->pure()->raw(), 0, $this->avatarsAmount)
                )
            );
    }
}