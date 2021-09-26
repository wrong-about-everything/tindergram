<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Basename;

use TG\Infrastructure\Http\Request\Url\Basename;
use TG\Infrastructure\Http\Request\Url\Path;

class FromPath implements Basename
{
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function value(): string
    {
        return basename($this->path->value());
    }
}