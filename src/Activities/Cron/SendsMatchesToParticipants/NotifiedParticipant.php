<?php

declare(strict_types=1);

namespace TG\Activities\Cron\SendsMatchesToParticipants;

use TG\Domain\Bot\BotToken\Impure\BotToken;
use TG\Domain\Bot\BotToken\Pure\FromImpure;
use TG\Domain\Participant\ParticipantId\Pure\ParticipantId;
use TG\Domain\Participant\WriteModel\Participant;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NotifiedParticipant implements Participant
{
    private $participantId;
    private $participantTelegramId;
    private $text;
    private $botToken;
    private $httpTransport;
    private $connection;

    public function __construct(ParticipantId $participantId, InternalTelegramUserId $participantTelegramId, string $text, BotToken $botToken, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->participantId = $participantId;
        $this->participantTelegramId = $participantTelegramId;
        $this->text = $text;
        $this->botToken = $botToken;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->participantTelegramId->value(),
                                'text' => $this->text,
                                'parse_mode' => 'MarkdownV2'
                            ]),
                            new FromImpure($this->botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return
            (new SingleMutating(
                'update meeting_round_pair set match_participant_contacts_sent = true where participant_id = ?',
                [$this->participantId->value()],
                $this->connection
            ))
                ->response();
    }
}