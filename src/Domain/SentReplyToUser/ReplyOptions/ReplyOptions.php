<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser\ReplyOptions;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface ReplyOptions
{
    public function value(): ImpureValue;
}