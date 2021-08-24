<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\Error;

use TG\Infrastructure\ImpureInteractions\Error;
use TG\Infrastructure\ImpureInteractions\Severity;
use TG\Infrastructure\ImpureInteractions\Severity\Alarm;

class AlarmDecline implements Error
{
    private $error;

    public function __construct(string $userMessage, string $logMessage, array $context)
    {
        $this->error = new Composite($userMessage, new Alarm(), $logMessage, $context);
    }

    public function userMessage(): string
    {
        return $this->error->userMessage();
    }

    public function severity(): Severity
    {
        return $this->error->severity();
    }

    public function logMessage(): string
    {
        return $this->error->logMessage();
    }

    public function context(): array
    {
        return $this->error->context();
    }
}