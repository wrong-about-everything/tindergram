<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FilePath;

use TG\Infrastructure\Filesystem\FileContents;
use TG\Infrastructure\Filesystem\FilePath;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;

class Removed extends FilePath
{
    private $filePath;
    private $cached;

    public function __construct(FilePath $filePath)
    {
        $this->filePath = $filePath;
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
        return $this->filePath->exists();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }
        if (!$this->filePath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not remove file %s because it does not exist', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $r = unlink($this->filePath->value()->pure()->raw());

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not remove %s', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        return new Successful(new Emptie());
    }
}