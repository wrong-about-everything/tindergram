<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\Basename;

use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Basename;
use TG\Infrastructure\Http\Request\Url\Path\FromUrl as PathFromUrl;

class FromUrl implements Basename
{
    private $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function value(): string
    {
        return (new FromPath(new PathFromUrl($this->url)))->value();
    }
}