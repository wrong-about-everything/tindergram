<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\Domain\Reply;

use Meringue\Timeline\Point\Now;
use TG\Domain\BooleanAnswer\BooleanAnswerName\NoMaybeNextTime;
use TG\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use TG\Domain\MeetingRound\ReadModel\MeetingRound;
use TG\Domain\MeetingRound\StartDateTime;
use TG\Domain\RoundInvitation\WriteModel\CreatedSent;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\HumanReadableDateTime\AccusativeDateTimeInMoscowTimeZone;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\Bot\BotToken\Pure\FromImpure;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class MeetingRoundInvitation implements SentReplyToUser
{
    private $meetingRound;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(MeetingRound $meetingRound, InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->meetingRound = $meetingRound;
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

        $telegramResponse =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' =>
                                    sprintf(
                                        <<<q
Спасибо за ответы!

У нас уже намечаются встречи, готовы поучаствовать? Пришлю вам пару %s, а когда и как встретиться, онлайн или оффлайн, договоритесь между собой.
q
                                        ,
                                        $this->meetingRoundHumanReadableStartDate()
                                    ),
                                'reply_markup' =>
                                    json_encode([
                                        'keyboard' => [
                                            [['text' => (new Sure())->value()]],
                                            [['text' => (new NoMaybeNextTime())->value()]],
                                        ],
                                        'resize_keyboard' => true,
                                        'one_time_keyboard' => true,
                                    ])
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$telegramResponse->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return (new CreatedSent($this->telegramUserId, $this->meetingRound, $this->connection))->value();
    }

    private function meetingRoundHumanReadableStartDate()
    {
        return (new AccusativeDateTimeInMoscowTimeZone(new Now(), new StartDateTime($this->meetingRound)))->value();
    }
}