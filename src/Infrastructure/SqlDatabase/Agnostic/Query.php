<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

interface Query
{
    public function response(): ImpureValue;
}
