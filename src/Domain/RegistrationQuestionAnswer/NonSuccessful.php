<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer;

use Exception;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful implements RegistrationQuestionAnswer
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('This class is only for non-successful impure values');
        }

        $this->impureValue = $impureValue;
    }

    public function exists(): ImpureValue
    {
        return $this->impureValue;
    }
}