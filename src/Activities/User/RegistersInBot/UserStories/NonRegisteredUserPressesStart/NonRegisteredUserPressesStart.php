<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\UserStories\NonRegisteredUserPressesStart;

use TG\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionSentToUser;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\MessageToUser\Sorry;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class NonRegisteredUserPressesStart extends Existent
{
    private $message;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Non-registered user presses start scenario started'));

        $registrationStepValue = $this->nextReply();
        if (!$registrationStepValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($registrationStepValue));
            $this->sorry()->value();
        }

        $this->logs->receive(new InformationMessage('Non-registered user presses start scenario finished'));

        return new Successful(new Emptie());
    }

    private function nextReply(): ImpureValue
    {
        return
            (new NextRegistrationQuestionSentToUser(
                new FromParsedTelegramMessage($this->message),
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function sorry(): SentReplyToUser
    {
        return
            new DefaultWithNoKeyboard(
                new FromParsedTelegramMessage($this->message),
                new Sorry(),
                $this->httpTransport
            );
    }
}