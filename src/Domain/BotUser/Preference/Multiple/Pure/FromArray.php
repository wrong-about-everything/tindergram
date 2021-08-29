<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Pure;

class FromArray extends PreferenceIds
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