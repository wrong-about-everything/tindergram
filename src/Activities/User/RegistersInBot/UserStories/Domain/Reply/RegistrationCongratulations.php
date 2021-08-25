<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\Domain\Reply;

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
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RegistrationCongratulations implements SentReplyToUser
{
    private $telegramUserId;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $telegramResponse = $this->congratulations();
        if (!$telegramResponse->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }

    private function congratulations()
    {
        return
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @hey_sweetie_support_bot',
                                'reply_markup' => json_encode(['remove_keyboard' => true])
                            ])
                        ),
                        [],
                        ''
                    )
                );
    }
}