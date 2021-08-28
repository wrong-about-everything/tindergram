<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\SentReplyToUser;

use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\Error\SilentDecline;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\KeyboardButtons\KeyboardButtons;
use TG\Infrastructure\TelegramBot\Method\SendMessage;

class DefaultWithMarkup implements SentReplyToUser
{
    private $telegramUserId;
    private $text;
    private $keyboardButtons;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, string $text, KeyboardButtons $keyboardButtons, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->text = $text;
        $this->keyboardButtons = $keyboardButtons;
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
                                        'text' => $this->text,
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
        if (empty($this->keyboardButtons->value())) {
            return [];
        }

        return [
            'reply_markup' =>
                json_encode([
                    'keyboard' => $this->keyboardButtons->value(),
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ])
        ];
    }
}