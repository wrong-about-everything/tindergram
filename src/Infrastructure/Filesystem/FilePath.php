<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

abstract class FilePath
{
    /**
     * @return ImpureValue Absolute path value
     */
    abstract public function value(): ImpureValue;

    abstract public function exists(): bool;

    final public function equals(FilePath $filePath): bool
    {
        return $this->value() === $filePath->value();
    }
}