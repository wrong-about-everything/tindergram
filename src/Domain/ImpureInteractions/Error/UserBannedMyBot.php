<?php

declare(strict_types=1);

namespace TG\Domain\ImpureInteractions\Error;

use TG\Infrastructure\ImpureInteractions\Error;
use TG\Infrastructure\ImpureInteractions\Severity;
use TG\Infrastructure\ImpureInteractions\Severity\Info;

class UserBannedMyBot extends Error
{
    public function userMessage(): string
    {
        return 'User banned my bot';
    }

    public function severity(): Severity
    {
        return new Info();
    }

    public function logMessage(): string
    {
        return 'User banned my bot';
    }

    public function context(): array
    {
        return [];
    }
}