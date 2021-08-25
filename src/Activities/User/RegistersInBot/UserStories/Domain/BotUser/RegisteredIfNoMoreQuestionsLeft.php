<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\Domain\BotUser;

use TG\Domain\BotUser\UserId\FromReadModelBotUser;
use TG\Domain\BotUser\WriteModel\BotUser;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RegisteredIfNoMoreQuestionsLeft implements BotUser
{
    private $internalTelegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->internalTelegramUserId = $telegramUserId;
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
        $updatedUserResponse =
            (new SingleMutating(
                <<<q
update bot_user
set status = ?
where telegram_id = ?
q

                ,
                [(new Registered())->value(), $this->internalTelegramUserId->value()],
                $this->connection
            ))
                ->response();
        if (!$updatedUserResponse->isSuccessful()) {
            return $updatedUserResponse;
        }

        return
            new Successful(
                new Present(
                    (new FromReadModelBotUser(
                        new ByInternalTelegramUserId($this->internalTelegramUserId, $this->connection)
                    ))
                        ->value()
                )
            );
    }
}