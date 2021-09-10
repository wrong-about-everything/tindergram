<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair\WriteModel;

use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsDown;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsUp;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple\LinedUpInARow;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\Emptie;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithInlineKeyboard;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstNNonDeleted;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;

class Sent implements ViewedPair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $pairName;
    private $httpTransport;
    private $cached;

    public function __construct(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, string $pairName, HttpTransport $httpTransport)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->pairName = $pairName;
        $this->httpTransport = $httpTransport;
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
        $sentAvatars =
            (new SentToUser(
                new Emptie(),
                new FirstNNonDeleted(
                    $this->pairTelegramId,
                    new FromTelegram(
                        $this->pairTelegramId,
                        $this->httpTransport
                    ),
                    5,
                    $this->httpTransport
                ),
                $this->recipientTelegramId,
                $this->httpTransport
            ))
                ->value();
        if (!$sentAvatars->isSuccessful()) {
            return $sentAvatars;
        }

        return
            (new DefaultWithInlineKeyboard(
                $this->recipientTelegramId,
                new FromString($this->pairName),
                new LinedUpInARow([
                    new ThumbsDown($this->pairTelegramId),
                    new ThumbsUp($this->pairTelegramId)
                ]),
                $this->httpTransport
            ))
                ->value();
    }
}