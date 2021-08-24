<?php

declare(strict_types=1);

namespace TG\Domain\Bot;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface Bot
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}