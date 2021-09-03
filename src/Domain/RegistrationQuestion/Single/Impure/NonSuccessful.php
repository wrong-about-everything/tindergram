<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use Exception;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends RegistrationQuestion
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('This class is only for non-successful values');
        }

        $this->impureValue = $impureValue;
    }

    public function id(): ImpureValue
    {
        return $this->impureValue;
    }

    public function ordinalNumber(): ImpureValue
    {
        return $this->impureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }
}