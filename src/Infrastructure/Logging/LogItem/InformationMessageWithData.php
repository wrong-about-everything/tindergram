<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\LogItem;

use Meringue\Timeline\Point\Now;
use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\Logging\Severity\Info;

class InformationMessageWithData implements LogItem
{
    private $message;
    private $data;

    public function __construct(string $message, array $data)
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function value(): array
    {
        return [
            'timestamp' => (new Now())->value(),
            'severity' => (new Info())->value(),
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
