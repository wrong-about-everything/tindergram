<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use TG\Domain\FeedbackInvitation\Status\Pure\Generated;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;

class FeedbackInvitation
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $records)
    {
        $response =
            (new SingleMutatingQueryWithMultipleValueSets(
                'insert into feedback_invitation (id, participant_id, status) values (?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [$values['id'], $values['participant_id'], $values['status']];
                    },
                    $records
                ),
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting meeting_round record: %s', $response->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'participant_id' => Uuid::uuid4()->toString(),
            'status' => (new Generated())->value(),
        ];
    }
}