<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\ReadModel;

use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextCandidateFor implements BotUser
{
    private $recipientTelegramId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $recipientTelegramId, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
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
        // Если будет медленно, попробовать
        //  - distinct on (recipient.telegram_id)
        //  - min (seen_qty)
        $value =
            (new Selecting(
                <<<query
    select candidate.*
    from bot_user recipient
        join bot_user candidate
            on recipient.preferred_gender = candidate.gender and recipient.gender = candidate.preferred_gender and recipient.telegram_id != candidate.telegram_id
        left join viewed_pair on viewed_pair.recipient_telegram_id = recipient.telegram_id and viewed_pair.pair_telegram_id = candidate.telegram_id
    where
        recipient.telegram_id = ?
            and
        viewed_pair.recipient_telegram_id is null
            and
        candidate.status = ?
            and
        candidate.has_avatar = ?
            and
        candidate.user_mode = ?
    order by candidate.seen_qty asc
    limit 1
query
                ,
                [$this->recipientTelegramId->value(), (new Registered())->value(), 1, (new Visible())->value()],
                $this->connection
            ))
                ->response();
        if (!$value->isSuccessful()) {
            return $value;
        }
        if (!isset($value->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($value->pure()->raw()[0]));
    }
}