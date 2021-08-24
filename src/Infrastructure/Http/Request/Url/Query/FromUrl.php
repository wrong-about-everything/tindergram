<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Request\Url\Query;

use Exception;
use TG\Infrastructure\Http\Request\Url;
use TG\Infrastructure\Http\Request\Url\Query;

class FromUrl implements Query
{
    private $query;

    public function __construct(Url $uri)
    {
        $queryPart = parse_url($uri->value(), PHP_URL_QUERY);

        if ($queryPart === false) {
            throw new Exception('Url is incorrect');
        }

        $this->query = $queryPart === null ? new NonSpecified() : new FromString($queryPart);
    }

    public function value(): string
    {
        return $this->query->value();
    }

    public function isSpecified(): bool
    {
        return $this->query->isSpecified();
    }
}
