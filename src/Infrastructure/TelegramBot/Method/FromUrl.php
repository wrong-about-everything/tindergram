<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\Method;

use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Path\FromUrl as PathFromUrl;

class FromUrl extends Method
{
    private $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function value(): string
    {
        return pathinfo((new PathFromUrl($this->url))->value())['basename'];
    }
}