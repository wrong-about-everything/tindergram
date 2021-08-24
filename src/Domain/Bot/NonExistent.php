<?php

declare(strict_types=1);

namespace TG\Domain\Bot;

use Exception;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class NonExistent implements Bot
{
    public function value(): ImpureValue
    {
        throw new Exception('Bot does not exist');
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present(false));
    }
}