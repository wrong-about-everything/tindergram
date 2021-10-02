<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class SwitchedToVisibleModeIfLike implements BotUser
{
    private $telegramUserId;
    private $reaction;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, Reaction $reaction, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->reaction = $reaction;
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
        if ($this->reaction->equals(new Like())) {
            $response =
                (new SingleMutating(
                    'update bot_user set user_mode = ? where telegram_id = ?',
                    [
                        (new Visible())->value(),
                        $this->telegramUserId->value(),
                    ],
                    $this->connection
                ))
                    ->response();
            if (!$response->isSuccessful()) {
                return $response;
            }
        }

        return new Successful(new Present($this->telegramUserId->value()));
    }
}