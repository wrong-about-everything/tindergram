<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Response\Code;

use TG\Infrastructure\Http\Response\Code;

class RetryableServerError extends Code
{
    public function value(): int
    {
        return 503;
    }
}