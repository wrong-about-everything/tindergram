<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\SentReplyToUser;

use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple\InlineKeyboardButtons;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;
use TG\Infrastructure\TelegramBot\Method\SendMessage;

class DefaultWithInlineKeyboard implements SentReplyToUser
{
    private $telegramUserId;
    private $messageToUser;
    private $inlineKeyboardButtons;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, MessageToUser $messageToUser, InlineKeyboardButtons $inlineKeyboardButtons, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->messageToUser = $messageToUser;
        $this->inlineKeyboardButtons = $inlineKeyboardButtons;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray(
                                array_merge(
                                    [
                                        'chat_id' => $this->telegramUserId->value(),
                                        'text' => $this->messageToUser->value(),
                                    ],
                                    $this->replyMarkup()
                                )
                            )
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new AlarmDeclineWithDefaultUserMessage('Response from telegram is not successful', []));
        }

        return new Successful(new Emptie());
    }

    private function replyMarkup()
    {
        if (empty($this->inlineKeyboardButtons->value())) {
            return [];
        }

        return [
            'reply_markup' =>
                json_encode([
                    'inline_keyboard' => $this->inlineKeyboardButtons->value()
                ])
        ];
    }
}