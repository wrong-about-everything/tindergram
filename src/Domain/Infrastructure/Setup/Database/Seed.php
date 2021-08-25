<?php

declare(strict_types=1);

namespace TG\Domain\Infrastructure\Setup\Database;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class Seed
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Emptie());
    }
}