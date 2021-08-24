<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\Logging\Severity\Error;
use Throwable;

class FromThrowable implements LogItem
{
    private $e;

    public function __construct(Throwable $e)
    {
        $this->e = $e;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Error())->value(),
            'message' => $this->e->getMessage(),
            'data' => $this->trace(),
        ];
    }

    private function trace()
    {
        return [
            'trace' => $this->e->getTraceAsString(),
            'file' => $this->e->getFile(),
            'line' => $this->e->getLine(),
        ];
    }
}
