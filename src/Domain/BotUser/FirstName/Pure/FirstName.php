<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\FirstName\Pure;

abstract class FirstName
{
    abstract public function value(): string;
}