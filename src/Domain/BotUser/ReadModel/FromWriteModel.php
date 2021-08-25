<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\ReadModel;

use TG\Domain\BotUser\UserId\FromWriteModelBotUser;
use TG\Domain\BotUser\WriteModel\BotUser as WriteModelBotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class FromWriteModel implements BotUser
{
    private $writeModelBotUser;
    private $connection;
    private $cached;

    public function __construct(WriteModelBotUser $writeModelBotUser, OpenConnection $connection)
    {
        $this->writeModelBotUser = $writeModelBotUser;
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
        if (!$this->writeModelBotUser->value()->isSuccessful()) {
            return $this->writeModelBotUser->value();
        }

        return (new ById(new FromWriteModelBotUser($this->writeModelBotUser), $this->connection))->value();
    }
}