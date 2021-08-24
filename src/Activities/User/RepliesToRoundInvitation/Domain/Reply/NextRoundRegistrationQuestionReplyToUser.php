<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\Domain\Reply;

use TG\Domain\SentReplyToUser\ReplyOptions\FromRoundRegistrationQuestion as AnswerOptionsFromRoundRegistrationQuestion;
use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use TG\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
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

class NextRoundRegistrationQuestionReplyToUser implements SentReplyToUser
{
    private $invitationId;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(InvitationId $invitationId, InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->invitationId = $invitationId;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $nextRoundRegistrationQuestion = new NextRoundRegistrationQuestion($this->invitationId, $this->connection);
        if (!$nextRoundRegistrationQuestion->value()->isSuccessful()) {
            return $nextRoundRegistrationQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($nextRoundRegistrationQuestion, $botToken);
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }
        // @todo: use the single infrastructure Reply class with retries

        return new Successful(new Emptie());
    }

    private function ask(RoundRegistrationQuestion $nextRoundRegistrationQuestion, BotToken $botToken)
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
                                        'text' => $nextRoundRegistrationQuestion->value()->pure()->raw()['text'],
                                    ],
                                    $this->replyMarkup($nextRoundRegistrationQuestion)
                                )
                            ),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }

    private function replyMarkup(RoundRegistrationQuestion $nextRegistrationQuestion)
    {
        $answerOptions = new AnswerOptionsFromRoundRegistrationQuestion($nextRegistrationQuestion, $this->invitationId, $this->connection);

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