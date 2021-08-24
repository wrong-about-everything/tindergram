<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\Domain\Reply;

use Meringue\Timeline\Point\Now;
use TG\Domain\MeetingRound\ReadModel\MeetingRound;
use TG\Domain\MeetingRound\StartDateTime;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\HumanReadableDateTime\AccusativeDateTimeInMoscowTimeZone;
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
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RoundRegistrationCongratulations implements SentReplyToUser
{
    private $telegramUserId;
    private $botId;
    private $meetingRound;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, MeetingRound $meetingRound, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->meetingRound = $meetingRound;
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
                                        'Поздравляю, вы зарегистрировались! %s пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
                                        $this->ucfirst((new AccusativeDateTimeInMoscowTimeZone(new Now(), new StartDateTime($this->meetingRound)))->value()),
                                    ),
                                'reply_markup' => json_encode(['remove_keyboard' => true])
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

        return new Successful(new Emptie());
    }

    private function ucfirst(string $s)
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }
}