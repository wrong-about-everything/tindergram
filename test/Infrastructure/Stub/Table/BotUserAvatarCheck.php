<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class BotUserAvatarCheck
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $records)
    {
        $botUserInsertResponse =
            (new SingleMutatingQueryWithMultipleValueSets(
                <<<q
insert into bot_user_avatar_check (telegram_id, date)
values (?, ?::date)
q
                ,
                array_map(
                    function (array $record) {
                        $v = array_merge($this->defaultValues(), $record);
                        return [
                            $v['telegram_id'], $v['date']
                        ];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$botUserInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting bot_user_avatar_check record: %s', $botUserInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [];
    }
}