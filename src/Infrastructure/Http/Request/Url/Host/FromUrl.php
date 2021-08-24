<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Url\Host;

use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Host;

class FromUrl implements Host
{
    private $host;

    public function __construct(Url $uri)
    {
        $this->host = parse_url($uri->value(), PHP_URL_HOST);
    }

    public function value(): string
    {
        return $this->host;
    }
}