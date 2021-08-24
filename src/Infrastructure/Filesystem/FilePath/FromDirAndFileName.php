<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FilePath;

use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\Filesystem\Filename;
use TG\Infrastructure\Filesystem\FilePath;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromDirAndFileName extends FilePath
{
    private $dirPath;
    private $filename;

    public function __construct(DirPath $dirPath, Filename $filename)
    {
        $this->dirPath = $dirPath;
        $this->filename = $filename;
    }

    public function value(): ImpureValue
    {
        if (!$this->dirPath->value()->isSuccessful()) {
            return $this->dirPath->value();
        }

        return
            new Successful(
                new Present(
                    sprintf(
                        '%s/%s',
                        $this->dirPath->value()->pure()->raw(),
                        $this->filename->value()
                    )
                )
            );
    }

    public function exists(): bool
    {
        return is_file($this->value()->pure()->raw());
    }
}