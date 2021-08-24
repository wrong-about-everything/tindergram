<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Query;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\Query;

class EmptyQuery implements Query
{
    public function response(): ImpureValue
    {
        return new Successful(new Present([]));
    }
}
