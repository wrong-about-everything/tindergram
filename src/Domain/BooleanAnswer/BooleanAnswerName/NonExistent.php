<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerName;

use Exception;

class NonExistent extends BooleanAnswerName
{
    public function value(): string
    {
        throw new Exception('BooleanAnswer name does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}