<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Pure;

use Exception;

class NonExistent implements RegistrationQuestion
{
    private $nonExistentRegistrationQuestionString;

    public function __construct(string $nonExistentRegistrationQuestionString)
    {
        $this->nonExistentRegistrationQuestionString = $nonExistentRegistrationQuestionString;
    }

    public function value(): string
    {
        throw new Exception(sprintf('Question %s is unknown', $this->nonExistentRegistrationQuestionString));
    }

    public function ordinalNumber(): int
    {
        throw new Exception(sprintf('Question %s is unknown', $this->nonExistentRegistrationQuestionString));
    }

    public function exists(): bool
    {
        return false;
    }
}