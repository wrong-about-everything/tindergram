<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use Meringue\Timeline\Point\Now;
use Ramsey\Uuid\Uuid;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class BotUser
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
insert into bot_user (
  id, first_name, last_name, telegram_id, telegram_handle,

  preferred_gender, gender, status, registered_at,

  is_initiated,

  seen_qty, last_seen_at, like_qty, dislike_qty
)
values (
  ?, ?, ?, ?, ?,

  ?, ?, ?, ?,

  ?,

  ?, ?, ?, ?
)
q
                ,
                array_map(
                    function (array $record) {
                        $v = array_merge($this->defaultValues(), $record);
                        return [
                            $v['id'], $v['first_name'], $v['last_name'], $v['telegram_id'], $v['telegram_handle'],

                            $v['preferred_gender'] ?? null, $v['gender'] ?? null, $v['status'] ?? null, $v['registered_at'] ?? null,

                            $v['is_initiated'],

                            $v['seen_qty'], $v['last_seen_at'], $v['like_qty'], $v['dislike_qty'],
                        ];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$botUserInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting bot_user record: %s', $botUserInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'first_name' => 'Vasya',
            'last_name' => 'Belov',
            'telegram_id' => 666,
            'telegram_handle' => 'vasya',

            'is_initiated' => 0,

            'seen_qty' => 0,
            'last_seen_at' => (new Now())->value(),
            'like_qty' => 0,
            'dislike_qty' => 0,
        ];
    }
}