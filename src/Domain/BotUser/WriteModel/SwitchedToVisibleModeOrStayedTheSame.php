<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use TG\Domain\ABTesting\Impure\FromBotUser as ExperimentVariantFromBotUser;
use TG\Domain\ABTesting\Pure\SwitchToVisibleModeOnUpvote;
use TG\Domain\BotUser\ReadModel\BotUser as ReadModelBotUser;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromBotUser;
use TG\Domain\UserMode\Impure\FromBotUser as BotUserMode;
use TG\Domain\UserMode\Impure\FromPure as ImpureMode;
use TG\Domain\UserMode\Pure\Invisible;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\ABTesting\Impure\FromPure;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class SwitchedToVisibleModeOrStayedTheSame implements BotUser
{
    private $botUser;
    private $reaction;
    private $logs;
    private $connection;
    private $cached;

    public function __construct(ReadModelBotUser $botUser, Reaction $reaction, Logs $logs, OpenConnection $connection)
    {
        $this->botUser = $botUser;
        $this->reaction = $reaction;
        $this->logs = $logs;
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
        if (
            $this->reaction->equals(new Like())
                &&
            (new BotUserMode($this->botUser))->exists()->pure()->isPresent()
                &&
            (new BotUserMode($this->botUser))->equals(new ImpureMode(new Invisible()))
                &&
            (new ExperimentVariantFromBotUser($this->botUser))->exists()->pure()->raw()
                &&
            (new ExperimentVariantFromBotUser($this->botUser))->equals(new FromPure(new SwitchToVisibleModeOnUpvote()))
        ) {
            $this->logs->receive(new InformationMessage('User turned visible'));
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