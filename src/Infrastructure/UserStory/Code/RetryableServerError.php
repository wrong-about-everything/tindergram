<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Code;

use TG\Infrastructure\UserStory\Code;

class RetryableServerError extends Code
{
    public function value(): int
    {
        return 2;
    }
}