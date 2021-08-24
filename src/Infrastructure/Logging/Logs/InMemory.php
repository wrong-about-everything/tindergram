<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\Logs;

use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs;

class InMemory implements Logs
{
    private $logId;
    private $storage;

    public function __construct(LogId $logId)
    {
        $this->logId = $logId;
        $this->storage = [];
    }

    public function receive(LogItem $item): void
    {
        $this->storage[] =
            array_merge(
                $item->value(),
                ['log_id' => $this->logId->value()]
            );
    }

    public function flush(): void
    {
        // these logs are not flushable. See GoogleCloudLogs as a counterexample.
    }

    public function all(): array
    {
        return $this->storage;
    }
}
