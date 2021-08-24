<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Impure;

use TG\Domain\BotUser\UserStatus\Pure\UserStatus as PureUserStatus;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends UserStatus
{
    private $pureUserStatus;

    public function __construct(PureUserStatus $pureUserStatus)
    {
        $this->pureUserStatus = $pureUserStatus;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureUserStatus->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureUserStatus->exists()));
    }
}