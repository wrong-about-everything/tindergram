<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\Logs;

use TG\Infrastructure\Filesystem\FilePath;
use TG\Infrastructure\Filesystem\FileContents\AppendedConcurrentSafelyToExistingFile;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\Logging\LogItem;
use Exception;

class File implements Logs
{
    private $filePath;
    private $logId;

    public function __construct(FilePath $filePath, LogId $logId)
    {
        $this->filePath = $filePath;
        $this->logId = $logId;
    }

    public function receive(LogItem $item): void
    {
        (new AppendedConcurrentSafelyToExistingFile(
            $this->filePath,
            json_encode(
                array_merge(
                    $item->value(),
                    ['log_id' => $this->logId->value()]
                )
            ) . PHP_EOL
        ))
            ->value();
    }

    public function flush(): void
    {
        // these logs are not flushable. See GoogleCloudLogs as a counterexample.
    }
}
