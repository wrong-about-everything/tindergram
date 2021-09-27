<?php

declare(strict_types=1);

namespace TG\UserActions\SendsArbitraryMessage;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\ReadModel\NextCandidateFor;
use TG\Domain\BotUser\UserStatus\Impure\UserStatus;
use TG\Domain\Pair\WriteModel\SentIfExists;
use TG\Domain\SentReplyToUser\InCaseOfAnyUncertainty;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromBotUser as InternalTelegramUserId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\ErrorFromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromImpure as PureInternalTelegramUserId;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\MessageToUser\Sorry;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\AnswersRegistrationQuestion;

class SendsArbitraryMessage extends Existent
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
        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario started'));

        $bot = $this->bot();
        $userStatus = $this->userStatus($bot);
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new ErrorFromNonSuccessfulImpureValue($userStatus->value()));
            $this->sorry();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            $this->answersRegistrationQuestion();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $sentPair = $this->sentPair($bot);
            if (!$sentPair->isSuccessful()) {
                $this->logs->receive(new ErrorFromNonSuccessfulImpureValue($userStatus->value()));
            }
        }

        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario finished'));

        return new Successful(new Emptie());
    }

    private function bot(): BotUser
    {
        return
            new ByInternalTelegramUserId(
                new FromParsedTelegramMessage($this->message),
                $this->connection
            );
    }

    private function userStatus(BotUser $botUser): UserStatus
    {
        return new FromBotUser($botUser);
    }

    private function answersRegistrationQuestion(): Response
    {
        return
            (new AnswersRegistrationQuestion(
                $this->message,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function sorry(): ImpureValue
    {
        return
            (new DefaultWithNoKeyboard(
                new FromParsedTelegramMessage($this->message),
                new Sorry(),
                $this->httpTransport
            ))
                ->value();
    }

    private function sentPair(BotUser $botUser): ImpureValue
    {
        return
            (new SentIfExists(
                new NextCandidateFor(
                    new PureInternalTelegramUserId(new InternalTelegramUserId($botUser)),
                    $this->connection
                ),
                new PureInternalTelegramUserId(new InternalTelegramUserId($botUser)),
                $this->httpTransport,
                $this->connection
            ))
                ->value();
    }
}