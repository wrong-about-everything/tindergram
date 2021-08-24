<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\DirPath;

use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;

class Created extends DirPath
{
    private $dirPath;
    private $cached;

    public function __construct(DirPath $dirPath)
    {
        $this->dirPath = $dirPath;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    public function exists(): bool
    {
        $this->value();
        return $this->dirPath->exists();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->dirPath->value()->isSuccessful()) {
            return $this->dirPath->value();
        }
        if ($this->dirPath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not create dir %s because it already exists', $this->dirPath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $r = mkdir($this->dirPath->value()->pure()->raw());

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not create dir %s', $this->dirPath->value()->pure()->raw()),
                        []
                    )
                );
        }

        return $this->dirPath->value();
    }
}