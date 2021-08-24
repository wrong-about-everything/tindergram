<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FileContents;

use TG\Infrastructure\Filesystem\FileContents;
use TG\Infrastructure\Filesystem\FilePath;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromFilePath implements FileContents
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

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }
        if (!$this->filePath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('File %s does not exist', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $contents = file_get_contents($this->filePath->value()->pure()->raw());

        return
            $contents === false
                ?
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not read contents of %s', $this->filePath->value()),
                        []
                    )
                )
                : new Successful(new Present($contents));
    }
}