<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply;

use TG\Domain\Bot\BotToken\Impure\BotToken;
use TG\Domain\Bot\BotToken\Pure\FromImpure;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class FeedbackInvitationDeclinedAndSeeYouNextTime implements SentReplyToUser
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
                                'text' => 'Тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
                                'reply_markup' => json_encode(['remove_keyboard' => true])
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