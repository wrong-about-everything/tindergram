<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Code;

use TG\Infrastructure\Http\Response\Code;

class Ok extends Code
{
    public function value(): int
    {
        return 200;
    }
}