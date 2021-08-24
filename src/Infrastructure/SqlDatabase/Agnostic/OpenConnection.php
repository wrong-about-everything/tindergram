<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic;

use PDO;
use Exception;

interface OpenConnection
{
    /**
     * @throws Exception
     * @todo Consider returning ImpureValue
     */
    public function value(): PDO;
}
