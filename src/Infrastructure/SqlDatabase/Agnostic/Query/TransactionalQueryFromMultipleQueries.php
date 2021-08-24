<?php

declare(strict_types=1);

namespace TG\Infrastructure\SqlDatabase\Agnostic\Query;

use Exception;
use TG\Infrastructure\Exception\StateCarrying;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Combined;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed as FailedImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query;
use Throwable;

class TransactionalQueryFromMultipleQueries implements Query
{
    private $queries;
    private $connection;

    public function __construct(array $queries, OpenConnection $connection)
    {
        $this->queries = $queries;
        $this->connection = $connection;
    }

    public function response(): ImpureValue
    {
        try {
            $dbh = $this->connection->value();
        } catch (Throwable $e) {
            return new FailedImpureValue(new AlarmDeclineWithDefaultUserMessage($e->getMessage(), $e->getTrace()));
        }

        $dbh->beginTransaction();

        try {
            $result =
                array_reduce(
                    $this->queries,
                    function (ImpureValue $compositeResult, Query $query) use ($dbh) {
                        $currentResponse = $query->response();
                        if (!$currentResponse->isSuccessful()) {
                            throw new StateCarrying($currentResponse->error()); // short-circuiting workaround
                        }

                        return new Combined($compositeResult, $currentResponse);
                    },
                    new Successful(new Emptie())
                )
            ;
        } catch (StateCarrying $e) {
            $dbh->rollBack();
            return new FailedImpureValue($e->error());
        }

        $dbh->commit();

        return $result;
    }
}
