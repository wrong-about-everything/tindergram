<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FileContents;

use TG\Infrastructure\Filesystem\FileContents;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class Emptie implements FileContents
{
    public function value(): ImpureValue
    {
        return new Successful(new Present(''));
    }
}