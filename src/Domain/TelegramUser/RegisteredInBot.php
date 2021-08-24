<?php

declare(strict_types=1);

namespace TG\Domain\TelegramUser;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Domain\Bot\BotId\BotId;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RegisteredInBot implements TelegramUser
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
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
                <<<q
select u.*
from
    "telegram_user" u join bot_user ub on u.id = ub.user_id
where u.telegram_id = ? and ub.bot_id = ?
q
                ,
                [$this->telegramUserId->value(), $this->botId->value()],
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