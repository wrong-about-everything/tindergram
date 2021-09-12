<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use Exception;
use TG\Infrastructure\Logging\Severity\Error;

class ErrorFromNonSuccessfulImpureValue implements LogItem
{
    private $impureValue;
    private $exception;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('You can log only *non-successful* impure values with this class');
        }

        $this->impureValue = $impureValue;
        $this->exception = new Exception();
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Error())->value(),
            'message' => $this->impureValue->error()->logMessage(),
            'data' => $this->data(),
        ];
    }

    private function data(): array
    {
        return [
            'context' => $this->impureValue->error()->context(),
            'trace' => $this->exception->getTraceAsString(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
        ];
    }
}
