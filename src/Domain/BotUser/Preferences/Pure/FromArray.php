<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preferences\Pure;

class FromArray extends Preferences
{
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function value(): array
    {
        return $this->array;
    }
}