<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\Domain\BotUser;

use TG\Domain\Bot\BotId\BotId;
use TG\Domain\BotUser\BotUser;
use TG\Domain\BotUser\ByTelegramUserId;
use TG\Domain\RegistrationQuestion\NextRegistrationQuestion;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId as PureTelegramUserId;

class RegisteredIfNoMoreQuestionsLeft implements BotUser
{
    private $telegramUserId;
    private $botId;
    private $connection;

    private $cached;

    public function __construct(PureTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
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
        if (!(new NextRegistrationQuestion($this->telegramUserId, $this->botId, $this->connection))->value()->pure()->isPresent()) {
            $updatedUserResponse =
                (new SingleMutating(
                    <<<q
    update bot_user
    set status = ?
    from "telegram_user"
    where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
    q

                    ,
                    [(new Registered())->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                ))
                    ->response();
            if (!$updatedUserResponse->isSuccessful()) {
                return $updatedUserResponse;
            }
        }

        return (new ByTelegramUserId($this->telegramUserId, $this->botId, $this->connection))->value();
    }
}