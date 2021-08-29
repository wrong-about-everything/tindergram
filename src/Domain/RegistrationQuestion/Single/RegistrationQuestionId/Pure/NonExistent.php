<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

use Exception;

class NonExistent extends RegistrationQuestionId
{
    public function value(): string
    {
        throw new Exception('Registration question id does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}