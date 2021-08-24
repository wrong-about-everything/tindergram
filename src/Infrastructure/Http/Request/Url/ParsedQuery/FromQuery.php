<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Request\Url\ParsedQuery;

use TG\Infrastructure\Http\Request\Url\ParsedQuery;
use TG\Infrastructure\Http\Request\Url\Query;

class FromQuery implements ParsedQuery
{
    private $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function value(): array
    {
        if (!$this->query->isSpecified()) {
            return [];
        }

        parse_str($this->query->value(), $parsedQuery);
        return $parsedQuery;
    }
}