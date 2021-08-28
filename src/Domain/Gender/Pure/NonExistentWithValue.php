<?php

declare(strict_types=1);

namespace TG\Domain\Gender\Pure;

use Exception;

class NonExistentWithValue extends Gender
{
    private $unknownGender;

    public function __construct(int $unknownGender)
    {
        $this->unknownGender = $unknownGender;
    }

    public function value(): int
    {
        throw new Exception(sprintf('Gender %s does not exist', $this->unknownGender));
    }

    public function exists(): bool
    {
        return false;
    }
}