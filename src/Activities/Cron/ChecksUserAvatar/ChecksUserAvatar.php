<?php

declare(strict_types=1);

namespace TG\Activities\Cron\ChecksUserAvatar;

use Meringue\Timeline\Point\Now;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromBotUserDatabaseRecord;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstN;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\UserAvatarIds;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class ChecksUserAvatar extends Existent
{
    private $now;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(Now $now, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->now = $now;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron checks user avatar scenario started'));

        array_map(
            function (array $botUser) {
                $everyNow = microtime(true);
                $this->updateUser(new FromBotUserDatabaseRecord($botUser));
                $diff = (int) ((microtime(true) - $everyNow) * 1000); // ms
                usleep($diff < 35 ? 35 - $diff : 0);
            },
            $this->usersNotCheckedToday()
        );

        $this->logs->receive(new InformationMessage('Cron checks user avatar scenario finished'));

        return new Successful(new Emptie());
    }

    private function userAvatars(InternalTelegramUserId $internalTelegramUserId): UserAvatarIds
    {
        return
            new FirstN(
                new FromTelegram(
                    $internalTelegramUserId,
                    $this->transport
                ),
                5
            );
    }

    private function usersNotCheckedToday(): array
    {
        return
            (new Selecting(
                <<<qqqq
select bu.*
from bot_user bu
    left join bot_user_avatar_check buac on bu.telegram_id = buac.telegram_id and buac.date = ?::date
where buac.telegram_id is null and bu.status = ? and bu.user_mode = ?
order by bu.telegram_id asc
limit 1700 -- (1000 / 35) * 60
qqqq
                ,
                [$this->now->value(), (new Registered())->value(), (new Visible())->value()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }

    private function updateUser(InternalTelegramUserId $telegramId)
    {
        $hasAvatarResponse = $this->updateHasAvatar($telegramId);
        if (!$hasAvatarResponse->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($hasAvatarResponse));
        }

        $userAvatarCheckedTodayResponse = $this->updateUserAvatarCheckedToday($telegramId);
        if (!$userAvatarCheckedTodayResponse->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userAvatarCheckedTodayResponse));
        }
    }

    private function updateHasAvatar(InternalTelegramUserId $telegramId)
    {
        $userAvatars = $this->userAvatars($telegramId)->value();

        return
            (new SingleMutating(
                'update bot_user set has_avatar = coalesce(?, has_avatar) where telegram_id = ?',
                [
                    $userAvatars->isSuccessful()
                        ? (count($userAvatars->pure()->raw()) > 0 ? 1 : 0)
                        : null,
                    $telegramId->value()
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateUserAvatarCheckedToday(InternalTelegramUserId $telegramId)
    {
        return
            (new SingleMutating(
                <<<q
insert into bot_user_avatar_check (telegram_id, date) values (?, ?::date)
    on conflict (telegram_id) do update set date = ?::date
q
                ,
                [$telegramId->value(), $this->now->value(), $this->now->value()],
                $this->connection
            ))
                ->response();
    }
}