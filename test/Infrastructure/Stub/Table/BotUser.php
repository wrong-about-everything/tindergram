<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use TG\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use TG\Domain\Position\PositionId\Pure\ProductManager;
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
                'insert into "bot_user" (id, user_id, bot_id, position, experience, about, status) values (?, ?, ?, ?, ?, ?, ?)',
                array_map(
                    function (array $record) {
                        $values = array_merge($this->defaultValues(), $record);
                        return [
                            $values['id'],
                            $values['user_id'],
                            $values['bot_id'],
                            $values['position'],
                            $values['experience'],
                            $values['about'],
                            $values['status']
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
            'position' => (new ProductManager())->value(),
            'experience' => (new LessThanAYear())->value(),
            'about' => 'About me',
            'status' => (new Registered())->value(),
        ];
    }
}