<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class TurnedActive implements BotUser
{
    private $telegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
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
            (new SingleMutating(
                'update bot_user set status = ? where telegram_id = ?',
                [(new Registered())->value(), $this->telegramUserId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }

        return new Successful(new Present($this->telegramUserId->value()));
    }
}