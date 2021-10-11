<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Impure\InactiveFromActive;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class TurnedInactiveFromActive implements BotUser
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
                [
                    (new InactiveFromActive(
                        new ByInternalTelegramUserId($this->telegramUserId, $this->connection)
                    ))
                        ->value()->pure()->raw(),
                    $this->telegramUserId->value()
                ],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }

        return new Successful(new Present($this->telegramUserId->value()));
    }
}