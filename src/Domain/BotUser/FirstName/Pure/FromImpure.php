<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\FirstName\Pure;

use TG\Domain\BotUser\FirstName\Impure\FirstName as ImpureFirstName;

class FromImpure extends FirstName
{
    private $impureFirstName;

    public function __construct(ImpureFirstName $impureFirstName)
    {
        $this->impureFirstName = $impureFirstName;
    }

    public function value(): string
    {
        return $this->impureFirstName->value()->pure()->raw();
    }
}