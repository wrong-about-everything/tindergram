<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\SentReplyToUser;

use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\KeyboardButtons\Emptie as EmptieKeyboard;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class DefaultWithNoKeyboard implements SentReplyToUser
{
    private $concrete;

    public function __construct(InternalTelegramUserId $telegramUserId, MessageToUser $messageToUser, HttpTransport $httpTransport)
    {
        $this->concrete = new DefaultWithKeyboard($telegramUserId, $messageToUser, new EmptieKeyboard(), $httpTransport);
    }

    public function value(): ImpureValue
    {
        return $this->concrete->value();
    }
}