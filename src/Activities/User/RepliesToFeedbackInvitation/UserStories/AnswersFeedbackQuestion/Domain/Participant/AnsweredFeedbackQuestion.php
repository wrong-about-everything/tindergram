<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AnswersFeedbackQuestion\Domain\Participant;

use TG\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use TG\Domain\FeedbackQuestion\FeedbackQuestion;
use TG\Domain\FeedbackQuestion\FeedbackQuestionId\Impure\FromFeedbackQuestion;
use TG\Domain\FeedbackQuestion\FeedbackQuestionId\Impure\FeedbackQuestionId;
use TG\Domain\Participant\ParticipantId\Impure\FromFeedbackInvitation;
use TG\Domain\Participant\ParticipantId\Impure\ParticipantId;
use TG\Domain\Participant\WriteModel\Participant;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class AnsweredFeedbackQuestion implements Participant
{
    private $userMessage;
    private $feedbackInvitation;
    private $answeredQuestion;
    private $connection;
    private $cached;

    public function __construct(FeedbackInvitation $feedbackInvitation, UserMessage $userMessage, FeedbackQuestion $answeredQuestion, OpenConnection $connection)
    {
        $this->feedbackInvitation = $feedbackInvitation;
        $this->userMessage = $userMessage;
        $this->answeredQuestion = $answeredQuestion;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $feedbackQuestionId = new FromFeedbackQuestion($this->answeredQuestion);
        if (!$feedbackQuestionId->value()->isSuccessful()) {
            return $feedbackQuestionId->value();
        }
        $participantId = new FromFeedbackInvitation($this->feedbackInvitation);
        if (!$participantId->value()->isSuccessful()) {
            return $participantId->value();
        }

        return $this->persistenceResponse($feedbackQuestionId, $participantId);
    }

    private function persistenceResponse(FeedbackQuestionId $feedbackQuestionId, ParticipantId $participantId)
    {
        return
            (new SingleMutating(
                <<<q
insert into feedback_answer (feedback_question_id, participant_id, text)
values (?, ?, ?)
q
                ,
                [$feedbackQuestionId->value()->pure()->raw(), $participantId->value()->pure()->raw(), $this->userMessage->value()],
                $this->connection
            ))
                ->response();
    }
}