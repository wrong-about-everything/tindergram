<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion;

use TG\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Reply\NextReplyToUser;
use TG\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Participant\ParticipantAnsweredRoundRegistrationQuestion;
use TG\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
use TG\Domain\SentReplyToUser\ReplyOptions\FromRoundRegistrationQuestion as AnswerOptionsFromRoundRegistrationQuestion;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundInvitation\ReadModel\LatestInvitation;
use TG\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use TG\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use TG\Domain\RoundRegistrationQuestion\Type\Impure\FromPure;
use TG\Domain\RoundRegistrationQuestion\Type\Impure\FromRoundRegistrationQuestion;
use TG\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use TG\Domain\SentReplyToUser\ValidationError;
use TG\Domain\UserInterest\InterestName\Pure\FromString;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage as UserReply;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\FromString as UuidFromString;

class AnswersRoundRegistrationQuestion extends Existent
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
        $this->logs->receive(new InformationMessage('User answers round invitation question scenario started.'));

        $invitationId = $this->invitationId();
        $currentlyAnsweredQuestion = new NextRoundRegistrationQuestion($invitationId, $this->connection);
        if ($this->isInvalid($currentlyAnsweredQuestion, new UserReply($this->message))) {
            $this->validationError(new AnswerOptionsFromRoundRegistrationQuestion($currentlyAnsweredQuestion, $invitationId, $this->connection))->value();
            return new Successful(new Emptie());
        }

        $participantValue = $this->participantAnsweredRoundRegistrationQuestion($invitationId, new UserReply($this->message), $currentlyAnsweredQuestion)->value();
        if (!$participantValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $nextReply = $this->nextReplyToUser($invitationId)->value();
        if (!$nextReply->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($nextReply));
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        $this->logs->receive(new InformationMessage('User answers round invitation question scenario finished.'));

        return new Successful(new Emptie());
    }

    private function validationError(ReplyOptions $answerOptions)
    {
        return
            new ValidationError(
                $answerOptions,
                new FromParsedTelegramMessage($this->message),
                new ByBotId(
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                ),
                $this->httpTransport
            );
    }

    private function isInvalid(RoundRegistrationQuestion $currentlyAnsweredQuestion, UserMessage $userReply): bool
    {
        return
            (
                (new FromRoundRegistrationQuestion($currentlyAnsweredQuestion))->equals(new FromPure(new NetworkingOrSomeSpecificArea()))
                &&
                !(new FromString($userReply->value()))->exists()
            );
    }

    private function invitationId()
    {
        return
            new FromInvitation(
                new LatestInvitation(
                    new FromParsedTelegramMessage($this->message),
                    new FromUuid(new UuidFromString($this->botId)),
                    $this->connection
                )
            );
    }

    private function participantAnsweredRoundRegistrationQuestion(InvitationId $invitationId, UserMessage $userMessage, RoundRegistrationQuestion $roundRegistrationQuestion)
    {
        return
            new ParticipantAnsweredRoundRegistrationQuestion(
                $userMessage,
                $invitationId,
                $roundRegistrationQuestion,
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

    private function nextReplyToUser(InvitationId $invitationId)
    {
        return
            new NextReplyToUser(
                $invitationId,
                new FromParsedTelegramMessage($this->message),
                new FromUuid(new UuidFromString($this->botId)),
                $this->httpTransport,
                $this->connection
            );
    }
}