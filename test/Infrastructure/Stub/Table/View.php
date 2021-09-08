<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use Meringue\Timeline\Point\Now;
use Ramsey\Uuid\Uuid;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class View
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $records)
    {
        $viewInsertResponse =
            (new SingleMutatingQueryWithMultipleValueSets(
                <<<q
insert into view (recipient_telegram_id, pair_telegram_id, viewed_at)
values (?, ?, ?)
q
                ,
                array_map(
                    function (array $record) {
                        $v = array_merge($this->defaultValues(), $record);
                        return [$v['recipient_telegram_id'], $v['pair_telegram_id'], $v['viewed_at']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$viewInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting view record: %s', $viewInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'viewed_at' => (new Now())->value(),
        ];
    }
}