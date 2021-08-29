<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\Preference\Multiple\Impure;

use Exception;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class NonSuccessful extends PreferenceIds
{
    private $impureValue;

    public function __construct(ImpureValue $impureValue)
    {
        if ($impureValue->isSuccessful()) {
            throw new Exception('This class can be used only with non-successful impure values');
        }

        $this->impureValue = $impureValue;
    }

    public function value(): ImpureValue
    {
        return $this->impureValue;
    }
}