<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface FileContents
{
    public function value(): ImpureValue;
}