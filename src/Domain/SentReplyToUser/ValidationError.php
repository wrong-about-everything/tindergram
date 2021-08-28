<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser;

use TG\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Domain\Bot\BotToken\Pure\FromImpure;
use TG\Domain\Bot\BotToken\Impure\BotToken;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

/**
 * @deprecated
 */
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
                                        'text' => 'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @hey_sweetie_support_bot',
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