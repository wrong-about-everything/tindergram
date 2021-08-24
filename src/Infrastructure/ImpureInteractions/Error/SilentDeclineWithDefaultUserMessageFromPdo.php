<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions\Error;

use PDO;
use TG\Infrastructure\ImpureInteractions\Error;
use TG\Infrastructure\ImpureInteractions\Severity;
use TG\Infrastructure\ImpureInteractions\Severity\Info;

class SilentDeclineWithDefaultUserMessageFromPdo implements Error
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function userMessage(): string
    {
        return 'Internal server error';
    }

    public function severity(): Severity
    {
        return new Info();
    }

    public function logMessage(): string
    {
        return 'Error from PDO driver';
    }

    public function context(): array
    {
        return $this->pdo->errorInfo();
    }
}