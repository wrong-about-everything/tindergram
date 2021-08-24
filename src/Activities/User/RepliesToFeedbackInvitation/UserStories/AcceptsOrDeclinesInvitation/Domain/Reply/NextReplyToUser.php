<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Reply;

use TG\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\NextFeedbackQuestionReplyToUser;
use TG\Activities\User\RepliesToFeedbackInvitation\Domain\Reply\ThanksForFeedback;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotToken\Impure\ByBotId;
use TG\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use TG\Domain\FeedbackInvitation\Status\Impure\FromFeedbackInvitation;
use TG\Domain\FeedbackInvitation\Status\Impure\FromPure;
use TG\Domain\FeedbackInvitation\Status\Pure\Declined;
use TG\Domain\FeedbackQuestion\FirstNonAnsweredFeedbackQuestion;
use TG\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation as ParticipantIdFromFeedbackInvitation;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\SentReplyToUser\SentReplyToUser;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextReplyToUser implements SentReplyToUser
{
    private $feedbackInvitation;
    private $telegramUserId;
    private $botId;
    private $httpTransport;
    private $connection;

    public function __construct(FeedbackInvitation $feedbackInvitation, InternalTelegramUserId $telegramUserId, BotId $botId, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->feedbackInvitation->value()->isSuccessful()) {
            return $this->feedbackInvitation->value();
        }

        $nextFeedbackQuestion = $this->nextFeedbackQuestion();
        if ((new FromFeedbackInvitation($this->feedbackInvitation))->equals(new FromPure(new Declined()))) {
            return $this->seeYouNextTime();
        } elseif (!$nextFeedbackQuestion->value()->pure()->isPresent()) {
            return $this->thanksForFeedback();
        } else {
            return
                (new NextFeedbackQuestionReplyToUser(
                    $nextFeedbackQuestion,
                    $this->telegramUserId,
                    $this->botId,
                    $this->connection,
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function seeYouNextTime()
    {
        return
            (new FeedbackInvitationDeclinedAndSeeYouNextTime(
                $this->telegramUserId,
                new ByBotId($this->botId, $this->connection),
                $this->httpTransport
            ))
                ->value();
    }

    private function thanksForFeedback()
    {
        return
            (new ThanksForFeedback(
                $this->telegramUserId,
                $this->botId,
                $this->connection,
                $this->httpTransport
            ))
                ->value();
    }

    private function nextFeedbackQuestion()
    {
        return
            new FirstNonAnsweredFeedbackQuestion(
                new ParticipantIdFromFeedbackInvitation($this->feedbackInvitation),
                $this->connection
            );
    }
}