<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\ReadModel;

use TG\Domain\BotUser\UserId\BotUserId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements BotUser
{
    private $botUserId;
    private $connection;
    private $cached;

    public function __construct(BotUserId $botUserId, OpenConnection $connection)
    {
        $this->botUserId = $botUserId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $response =
            (new Selecting(
                'select bu.* from bot_user bu where bu.id = ?',
                [$this->botUserId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}