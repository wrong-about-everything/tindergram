<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

use Exception;

class NonExistentWithId implements RegistrationQuestion
{
    private $nonExistentRegistrationQuestionId;

    public function __construct(string $nonExistentRegistrationQuestionId)
    {
        $this->nonExistentRegistrationQuestionId = $nonExistentRegistrationQuestionId;
    }

    public function id(): string
    {
        throw new Exception(sprintf('Question with id = %s is unknown', $this->nonExistentRegistrationQuestionId));
    }

    public function ordinalNumber(): int
    {
        throw new Exception(sprintf('Question with id = %s is unknown', $this->nonExistentRegistrationQuestionId));
    }

    public function exists(): bool
    {
        return false;
    }
}