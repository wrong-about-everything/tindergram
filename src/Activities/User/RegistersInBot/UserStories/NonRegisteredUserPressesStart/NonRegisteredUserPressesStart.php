<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\NonRegisteredUserPressesStart;

use TG\Activities\User\RegistersInBot\UserStories\Domain\Reply\NextReplyToUserToUser;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\SentReplyToUser\InCaseOfAnyUncertainty;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString as UuidFromString;

class NonRegisteredUserPressesStart extends Existent
{
    private $message;
    private $botId;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, string $botId, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User presses start during registration scenario started'));

        $registrationStepValue = $this->nextReply()->value();
        if (!$registrationStepValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($registrationStepValue));
            $this->sorry()->value();
        }

        $this->logs->receive(new InformationMessage('User presses start during registration scenario finished'));

        return new Successful(new Emptie());
    }

    private function nextReply()
    {
        return
            new InCaseOfAnyUncertainty(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->connection,
                $this->httpTransport
            );
            new NextReplyToUserToUser(
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }
}