<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FileContents;

use TG\Infrastructure\Filesystem\FileContents;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromStdErr implements FileContents
{
    private $value;

    public function __construct()
    {
        $this->value = new Successful(new Present(file_get_contents('php://stderr')));
    }

    public function value(): ImpureValue
    {
        return $this->value;
    }
}