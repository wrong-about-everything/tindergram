<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId as PureInternalTelegramUserId;

class FromPure extends InternalTelegramUserId
{
    private $pureInternalTelegramUserId;

    public function __construct(PureInternalTelegramUserId $pureInternalTelegramUserId)
    {
        $this->pureInternalTelegramUserId = $pureInternalTelegramUserId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureInternalTelegramUserId->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureInternalTelegramUserId->exists()));
    }
}