<?php

declare(strict_types=1);

namespace TG\Infrastructure\Logging\Logs;

use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Infrastructure\Logging\LogItem;
use TG\Infrastructure\Logging\Logs;

class EnvironmentDependentLogs implements Logs
{
    private $concrete;

    public function __construct(DirPath $root, Logs $local, Logs $prod)
    {
        $this->concrete = $this->concrete($root, $local, $prod);
    }

    public function receive(LogItem $item): void
    {
        $this->concrete->receive($item);
    }

    public function flush(): void
    {
        $this->concrete->flush();
    }

    private function concrete(DirPath $root, Logs $local, Logs $prod): Logs
    {
        if ((new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()) {
            return $local;
        }

        return $prod;
    }
}
