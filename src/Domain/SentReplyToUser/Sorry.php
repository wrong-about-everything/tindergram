<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser;

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

class Sorry implements SentReplyToUser
{
    private $telegramUserId;
    private $botToken;
    private $httpTransport;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, BotToken $botToken, HttpTransport $httpTransport)
    {
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
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Простите, у нас что-то сломалось. Попробуйте ещё пару раз, и если не заработает — напишите, пожалуйста, в @tindergram_support_bot',
                            ]),
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