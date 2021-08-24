<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Query;

use Exception;
use PDO;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed as FailedImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query;
use Throwable;

class Selecting implements Query
{
    private $queryString;
    private $values;
    private $connection;
    private $exceptionForTrace;

    public function __construct(string $queryString, array $values, OpenConnection $connection)
    {
        $this->queryString = $queryString;
        $this->values = array_values($values);
        $this->connection = $connection;
        $this->exceptionForTrace = new Exception();
    }

    public function response(): ImpureValue
    {
        try {
            $dbh = $this->connection->value();
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        try {
            $statement =
                $dbh->prepare(
                    (new QueryStringWithCorrectQuestionMarksQuantityInClausesWithArrays(
                        $this->queryString,
                        $this->values
                    ))
                        ->value()
                );

            $result = $statement->execute((new FlatValues($this->values))->value());
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        if ($result === false) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($statement->errorInfo()[2], $this->exceptionForTrace->getTrace()));
        }

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $statement->closeCursor();
        $statement = null;

        return new Successful(new Present($result));
    }
}
