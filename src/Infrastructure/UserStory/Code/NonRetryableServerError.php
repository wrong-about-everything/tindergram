<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Code;

use TG\Infrastructure\UserStory\Code;

class NonRetryableServerError extends Code
{
    public function value(): int
    {
        return 3;
    }
}