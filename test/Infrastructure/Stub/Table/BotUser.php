<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
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
insert into bot_user (id, first_name, last_name, telegram_id, telegram_handle, status, preferences, gender)
values (?, ?, ?, ?, ?, ?, ?, ?)
q
                ,
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [
                            $values['id'],
                            $values['first_name'],
                            $values['last_name'],
                            $values['telegram_id'],
                            $values['telegram_handle'],
                            $values['status'],
                            is_null($values['preferences']) ? null : json_encode(is_null($values['preferences'])),
                            $values['gender']
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
            'telegram_handle' => '@vasya',
            'status' => (new Registered())->value(),
            'preferences' => null,
            'gender' => null,
        ];
    }
}