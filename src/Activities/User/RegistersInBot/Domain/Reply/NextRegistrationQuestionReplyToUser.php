<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\Domain\Reply;

use RC\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion as AnswerOptionsFromRegistrationQuestion;
use RC\Domain\RegistrationQuestion\NextRegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestion;
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
use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class NextRegistrationQuestionReplyToUser implements SentReplyToUser
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
        $nextRegistrationQuestion = new NextRegistrationQuestion($this->telegramUserId, $this->botId, $this->connection);
        if (!$nextRegistrationQuestion->value()->isSuccessful()) {
            return $nextRegistrationQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($nextRegistrationQuestion, $botToken);
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }

    private function ask(RegistrationQuestion $nextRegistrationQuestion, BotToken $botToken)
    {
        return
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
                                        'text' => $nextRegistrationQuestion->value()->pure()->raw()['text'],
                                    ],
                                    $this->replyMarkup($nextRegistrationQuestion)
                                )
                            ),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }

    private function replyMarkup(RegistrationQuestion $nextRegistrationQuestion)
    {
        $answerOptions = new AnswerOptionsFromRegistrationQuestion($nextRegistrationQuestion, $this->botId, $this->connection);

        if (empty($answerOptions->value()->pure()->raw())) {
            return [];
        }

        return [
            'reply_markup' =>
                json_encode([
                    'keyboard' => $answerOptions->value()->pure()->raw(),
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ])
        ];
    }
}