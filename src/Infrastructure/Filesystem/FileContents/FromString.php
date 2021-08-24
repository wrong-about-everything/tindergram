<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FileContents;

use TG\Infrastructure\Filesystem\FileContents;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromString implements FileContents
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->content));
    }
}