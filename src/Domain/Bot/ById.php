<?php

declare(strict_types=1);

namespace TG\Domain\Bot;

use TG\Domain\Bot\BotId\BotId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements Bot
{
    private $botId;
    private $connection;
    private $cached;

    public function __construct(BotId $botId, OpenConnection $connection)
    {
        $this->botId = $botId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): Bot
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Bot
    {
        $response =
            (new Selecting(
                'select * from bot where id = ?',
                [$this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return new NonSuccessful($response);
        }
        if (!$response->pure()->isPresent()) {
            return new NonExistent();
        }

        return new FromArray($response->pure()->raw()[0]);
    }
}