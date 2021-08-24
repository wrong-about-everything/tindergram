<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Filesystem\DirPath;

use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent extends DirPath
{
    public function value(): ImpureValue
    {
        return new Successful(new Present('/most/likely/i/do/not/exist'));
    }

    public function exists(): bool
    {
        return file_exists('/most/likely/i/do/not/exist');
    }
}