<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\Domain\Reply;

use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class RegistrationCongratulations implements SentReplyToUser
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $telegramResponse = $this->congratulations($botToken);
        if (!$telegramResponse->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }

    private function congratulations(BotToken $botToken)
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
                                'text' => 'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
                                'reply_markup' => json_encode(['remove_keyboard' => true])
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }
}