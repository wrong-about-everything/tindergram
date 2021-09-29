<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use TG\Domain\BotUser\FirstName\Impure\FromBotUser as BotUserFirstName;
use TG\Domain\BotUser\FirstName\Pure\FirstName;
use TG\Domain\BotUser\FirstName\Pure\FromImpure as PureFirstName;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\WriteModel\IncrementedViewsQty;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsDown;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsUp;
use TG\Domain\TelegramBot\MessageToUser\ThatsAllForNow;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple\LinedUpInARow;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromBotUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromImpure;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\Emptie;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithInlineKeyboardAndRemovedKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstN;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;

class SentIfExists implements Pair
{
    private $candidate;
    private $recipientTelegramId;
    private $transport;
    private $connection;
    private $cached;

    public function __construct(
        BotUser $candidate,
        InternalTelegramUserId $recipientTelegramId,
        HttpTransport $transport,
        OpenConnection $connection
    )
    {
        $this->candidate = $candidate;
        $this->recipientTelegramId = $recipientTelegramId;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            if (!$this->candidate->value()->isSuccessful()) {
                return $this->candidate->value();
            }
            if (!$this->candidate->value()->pure()->isPresent()) {
                return $this->thatsAllForNow()->value();
            }
            $candidateTelegramId = new FromBotUser($this->candidate);
            if (!$candidateTelegramId->value()->isSuccessful()) {
                return $candidateTelegramId->value();
            }
            $candidateName = new BotUserFirstName($this->candidate);
            if (!$candidateName->value()->isSuccessful()) {
                return $candidateName->value();
            }

            $this->cached = $this->doValue(new FromImpure($candidateTelegramId), new PureFirstName($candidateName));
        }

        return $this->cached;
    }

    private function doValue(InternalTelegramUserId $candidateTelegramId, FirstName $firstName): ImpureValue
    {
        $sentPair = $this->sent($candidateTelegramId, $firstName);
        if (!$sentPair->isSuccessful()) {
            return $sentPair;
        }

        $persistentPair = $this->persistent($candidateTelegramId);
        if (!$persistentPair->value()->isSuccessful()) {
            return $persistentPair->value();
        }

        return (new IncrementedViewsQty($candidateTelegramId, $this->connection))->value();
    }

    private function thatsAllForNow(): MessageSentToUser
    {
        return new DefaultWithNoKeyboard($this->recipientTelegramId, new ThatsAllForNow(), $this->transport);
    }

    private function sent(InternalTelegramUserId $candidateTelegramId, FirstName $firstName): ImpureValue
    {
        $sentAvatars = $this->sentAvatars($candidateTelegramId);
        if (!$sentAvatars->isSuccessful()) {
            return $sentAvatars;
        }

        return $this->sentInfoAndRatingButtons($candidateTelegramId, $firstName);
    }

    private function sentAvatars(InternalTelegramUserId $candidateTelegramId): ImpureValue
    {
        return
            (new SentToUser(
                new Emptie(),
                new FirstN(
                    new FromTelegram(
                        $candidateTelegramId,
                        $this->transport
                    ),
                    5
                ),
                $this->recipientTelegramId,
                $this->transport
            ))
                ->value();
    }

    private function sentInfoAndRatingButtons(InternalTelegramUserId $candidateTelegramId, FirstName $firstName): ImpureValue
    {
        return
            (new DefaultWithInlineKeyboardAndRemovedKeyboard(
                $this->recipientTelegramId,
                new FromString($firstName->value()),
                new LinedUpInARow([
                    new ThumbsDown($candidateTelegramId),
                    new ThumbsUp($candidateTelegramId)
                ]),
                $this->transport
            ))
                ->value();
    }

    private function persistent(InternalTelegramUserId $candidateTelegramId): Pair
    {
        return new Persistent($this->recipientTelegramId, $candidateTelegramId, $this->connection);
    }
}