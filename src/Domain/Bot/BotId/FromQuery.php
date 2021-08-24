<?php

declare(strict_types=1);

namespace TG\Domain\Bot\BotId;

use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery as ParsedQuery;
use TG\Infrastructure\Http\Request\Url\Query;
use TG\Infrastructure\Uuid\FromString;

class FromQuery extends BotId
{
    private $botId;

    public function __construct(Query $query)
    {
        $this->botId =
            isset((new ParsedQuery($query))->value()['secret_smile'])
                ? new FromUuid(new FromString((new ParsedQuery($query))->value()['secret_smile']))
                : new NonExistent()
        ;
    }

    public function value(): string
    {
        return $this->botId->value();
    }

    public function exists(): bool
    {
        return $this->botId->exists();
    }
}