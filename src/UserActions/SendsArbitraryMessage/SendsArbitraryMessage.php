<?php

declare(strict_types=1);

namespace TG\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\AcceptsOrDeclinesFeedbackInvitation;
use TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\AnswersFeedbackQuestion;
use TG\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\AnswersRoundRegistrationQuestion;
use TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\AcceptsOrDeclinesInvitation;
use TG\Domain\BotUser\ByTelegramUserId;
use TG\Domain\SentReplyToUser\InCaseOfAnyUncertainty;
use TG\Domain\SentReplyToUser\NoRoundsAhead;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString as UuidFromString;
use TG\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\AnswersRegistrationQuestion;

class SendsArbitraryMessage extends Existent
{
    private $now;
    private $message;
    private $botId;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(ISO8601DateTime $now, array $message, string $botId, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->now = $now;
        $this->message = $message;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario started'));

        $userStatus = $this->userStatus();
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userStatus->value()));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            $this->answersRegistrationQuestion();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $this->showNewPair()->value();
        }

        $this->logs->receive(new InformationMessage('User sends arbitrary message scenario finished'));

        return new Successful(new Emptie());
    }

    private function userStatus()
    {
        return
            new FromBotUser(
                new ByTelegramUserId(
                    new FromParsedTelegramMessage($this->message),
                    $this->botId(),
                    $this->connection
                )
            );
    }

    private function answersRegistrationQuestion()
    {
        return
            (new AnswersRegistrationQuestion(
                $this->message,
                $this->botId,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
    }

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    $this->botId(),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function showNewPair()
    {
        return
            new InCaseOfAnyUncertainty(
                new FromParsedTelegramMessage($this->message),
                $this->botId(),
                $this->connection,
                $this->httpTransport
            );
    }

    private function botId()
    {
        return new FromUuid(new UuidFromString($this->botId));
    }
}