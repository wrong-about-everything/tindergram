<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\Logs;

use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\Logging\Logs;

class DevNull implements Logs
{
    public function receive(LogItem $item): void
    {
    }

    public function flush(): void
    {
        // these logs are not flushable. See GoogleCloudLogs as a counterexample.
    }
}
