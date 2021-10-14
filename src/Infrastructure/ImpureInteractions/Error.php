<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions;

interface Error
{
    public function userMessage(): string;

    public function severity(): Severity;

    public function logMessage(): string;

    public function context(): array;
}