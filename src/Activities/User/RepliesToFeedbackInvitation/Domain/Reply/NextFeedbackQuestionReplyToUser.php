<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\Domain\Reply;

use TG\Domain\FeedbackQuestion\FeedbackQuestion;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
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

class NextFeedbackQuestionReplyToUser implements SentReplyToUser
{
    private $feedbackQuestion;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(FeedbackQuestion $feedbackInvitation, InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->feedbackQuestion = $feedbackInvitation;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        if (!$this->feedbackQuestion->value()->isSuccessful()) {
            return $this->feedbackQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($botToken);
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not successful', []));
        }

        return new Successful(new Emptie());
    }

    private function ask(BotToken $botToken)
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
                                'text' => $this->feedbackQuestion->value()->pure()->raw()['text'],
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }
}