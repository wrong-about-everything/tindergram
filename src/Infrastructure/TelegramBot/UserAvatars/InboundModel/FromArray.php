<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromArray implements UserAvatarIds
{
    private $avatarsIds;

    public function __construct(array $avatarsIds)
    {
        $this->avatarsIds = $avatarsIds;
    }

    public function value(): ImpureValue/*array*/
    {
        return new Successful(new Present($this->avatarsIds));
    }
}