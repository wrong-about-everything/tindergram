<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\WriteModel;

use Ramsey\Uuid\Uuid;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserId\FromReadModelBotUser;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class AddedIfNotYet implements BotUser
{
    private $telegramUserId;
    private $firstName;
    private $lastName;
    private $telegramHandle;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, string $firstName, string $lastName, string $telegramHandle, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->telegramHandle = $telegramHandle;
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
        $botUserFromDb = new ByInternalTelegramUserId($this->telegramUserId, $this->connection);
        if (!$botUserFromDb->value()->isSuccessful()) {
            return $botUserFromDb->value();
        }
        if ($botUserFromDb->value()->pure()->isPresent()) {
            return new Successful(new Present((new FromReadModelBotUser($botUserFromDb))->value()));
        }

        $generatedUserId = Uuid::uuid4()->toString();

        $registerUserResponse =
            (new SingleMutating(
                <<<q
insert into bot_user (id, first_name, last_name, telegram_id, telegram_handle, status)
values (?, ?, ?, ?, ?, ?)
q
                ,
                [
                    $generatedUserId,
                    $this->firstName,
                    $this->lastName,
                    $this->telegramUserId->value(),
                    $this->telegramHandle,
                    (new RegistrationIsInProgress())->value()
                ],
                $this->connection
            ))
                ->response();
        if (!$registerUserResponse->isSuccessful()) {
            return $registerUserResponse;
        }

        return new Successful(new Present($generatedUserId));
    }
}