<?php

declare(strict_types=1);

namespace RC\Domain\SentReplyToUser;

use RC\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class ValidationError implements SentReplyToUser
{
    private $answerOptions;
    private $telegramUserId;
    private $botToken;
    private $httpTransport;
    private $cached;

    public function __construct(ReplyOptions $answerOptions, InternalTelegramUserId $telegramUserId, BotToken $botToken, HttpTransport $httpTransport)
    {
        $this->answerOptions = $answerOptions;
        $this->telegramUserId = $telegramUserId;
        $this->botToken = $botToken;
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
        if (!$this->botToken->value()->isSuccessful()) {
            return $this->botToken->value();
        }

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
                                        'text' => 'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @gorgonzola_support_bot',
                                    ],
                                    empty($this->answerOptions->value())
                                        ? []
                                        :
                                            [
                                                'reply_markup' =>
                                                    json_encode([
                                                        'keyboard' => $this->answerOptions->value()->pure()->raw(),
                                                        'resize_keyboard' => true,
                                                        'one_time_keyboard' => true,
                                                    ])
                                            ]
                                )
                            ),
                            new FromImpure($this->botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }
}