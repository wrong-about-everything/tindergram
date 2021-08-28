<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion as AnswerOptionsFromRegistrationQuestion;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextRegistrationQuestionReply implements SentReplyToUser
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

        $response = $this->ask($nextRegistrationQuestion, $botToken);
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }

    private function ask(RegistrationQuestion $nextRegistrationQuestion): ImpureValue
    {
        return
            (new DefaultWithMarkup(
                $nextRegistrationQuestion->value()->pure()->raw()['text'],
                new AnswerOptionsFromRegistrationQuestion($nextRegistrationQuestion, $this->connection)
            ))
                ->value();
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