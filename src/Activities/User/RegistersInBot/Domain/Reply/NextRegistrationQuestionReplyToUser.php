<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion as AnswerOptionsFromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\NextRegistrationQuestion;
use TG\Domain\RegistrationQuestion\RegistrationQuestion;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\Bot\BotToken\Pure\FromImpure;
use TG\Domain\Bot\BotToken\Impure\BotToken;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextRegistrationQuestionReplyToUser implements SentReplyToUser
{
    private $telegramUserId;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
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