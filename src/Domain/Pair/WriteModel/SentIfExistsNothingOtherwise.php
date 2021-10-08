<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\SentReplyToUser\Nothing;

class SentIfExistsNothingOtherwise implements Pair
{
    private $concrete;

    public function __construct(BotUser $candidate, InternalTelegramUserId $recipientTelegramId, HttpTransport $transport, OpenConnection $connection)
    {
        $this->concrete =
            new SentIfExists(
                $candidate,
                $recipientTelegramId,
                new Nothing(),
                $transport,
                $connection
            );
    }

    public function value(): ImpureValue
    {
        return $this->concrete->value();
    }
}