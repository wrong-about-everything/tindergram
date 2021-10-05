<?php

declare(strict_types=1);

namespace TG\Activities\User\BansBot;

use TG\Domain\BotUser\WriteModel\Inactive;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class BansBot extends Existent
{
    private $internalTelegramUserId;
    private $connection;
    private $logs;

    public function __construct(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection, Logs $logs)
    {
        $this->internalTelegramUserId = $internalTelegramUserId;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User bans bot scenario started'));

        $botUserValue = (new Inactive($this->internalTelegramUserId, $this->connection))->value();
        if (!$botUserValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($botUserValue));
        }

        $this->logs->receive(new InformationMessage('User bans bot scenario finished'));

        return new Successful(new Emptie());
    }
}