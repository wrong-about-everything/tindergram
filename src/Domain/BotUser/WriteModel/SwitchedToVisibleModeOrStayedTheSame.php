<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Domain\ABTesting\Impure\FromBotUser as ExperimentVariantFromBotUser;
use TG\Domain\ABTesting\Pure\SwitchToVisibleModeOnUpvote;
use TG\Domain\BotUser\ReadModel\BotUser as ReadModelBotUser;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromBotUser;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\ABTesting\Impure\FromPure;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class SwitchedToVisibleModeOrStayedTheSame implements BotUser
{
    private $botUser;
    private $reaction;
    private $connection;
    private $cached;

    public function __construct(ReadModelBotUser $botUser, Reaction $reaction, OpenConnection $connection)
    {
        $this->botUser = $botUser;
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
        if ($this->reaction->equals(new Like()) && (new ExperimentVariantFromBotUser($this->botUser))->equals(new FromPure(new SwitchToVisibleModeOnUpvote()))) {
            $response =
                (new SingleMutating(
                    'update bot_user set user_mode = ? where telegram_id = ?',
                    [
                        (new Visible())->value(),
                        (new FromBotUser($this->botUser))->value()->pure()->raw(),
                    ],
                    $this->connection
                ))
                    ->response();
            if (!$response->isSuccessful()) {
                return $response;
            }
        }

        return
            new Successful(
                new Present(
                    (new FromBotUser($this->botUser))->value()->pure()->raw()
                )
            );
    }
}