<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserMessage\Impure;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage as PureUserMessage;

class FromPure implements UserMessage
{
    private $pureUserMessage;

    public function __construct(PureUserMessage $pureUserMessage)
    {
        $this->pureUserMessage = $pureUserMessage;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureUserMessage->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureUserMessage->exists()));
    }
}