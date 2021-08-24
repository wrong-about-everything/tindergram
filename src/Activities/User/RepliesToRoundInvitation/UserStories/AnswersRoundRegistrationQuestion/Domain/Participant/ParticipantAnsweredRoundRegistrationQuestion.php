<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Participant;

use Exception;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromPure;
use RC\Domain\RoundRegistrationQuestion\Type\Impure\FromRoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInterestName;
use RC\Domain\UserInterest\InterestName\Pure\FromString as InterestNameFromString;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure\FromRoundRegistrationQuestion as RoundRegistrationQuestionId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\FromImpure;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\RoundRegistrationQuestionId as PureRoundRegistrationQuestionId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class ParticipantAnsweredRoundRegistrationQuestion implements Participant
{
    private $userMessage;
    private $invitationId;
    private $answeredQuestion;
    private $connection;
    private $cached;

    public function __construct(UserMessage $userMessage, InvitationId $invitationId, RoundRegistrationQuestion $answeredQuestion, OpenConnection $connection)
    {
        $this->userMessage = $userMessage;
        $this->invitationId = $invitationId;
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
        $roundRegistrationQuestionId = new RoundRegistrationQuestionId($this->answeredQuestion);
        if (!$roundRegistrationQuestionId->value()->isSuccessful()) {
            return $roundRegistrationQuestionId->value();
        }

        $updateProgressResponse = $this->persistenceResponse(new FromImpure($roundRegistrationQuestionId));
        if (!$updateProgressResponse->isSuccessful()) {
            return $updateProgressResponse;
        }

        return new Successful(new Emptie());
    }

    private function persistenceResponse(PureRoundRegistrationQuestionId $roundRegistrationQuestionId)
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into user_round_registration_progress (registration_question_id, user_id)
select ?, user_id from meeting_round_invitation where id = ?
q
                        ,
                        [$roundRegistrationQuestionId->value(), $this->invitationId->value()->pure()->raw()],
                        $this->connection
                    ),
                    $this->updateBotUserQuery(),
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateBotUserQuery()
    {
        if ((new FromRoundRegistrationQuestion($this->answeredQuestion))->equals(new FromPure(new NetworkingOrSomeSpecificArea()))) {
            return
                new SingleMutating(
                    <<<q
update meeting_round_participant
set interested_in = ?
from meeting_round_invitation mri
where mri.user_id = meeting_round_participant.user_id and mri.meeting_round_id = meeting_round_participant.meeting_round_id and mri.id = ?
q
                    ,
                    [
                        json_encode(
                            [
                                (new FromInterestName(
                                    new InterestNameFromString($this->userMessage->value())
                                ))
                                    ->value()
                            ]
                        ),
                        $this->invitationId->value()->pure()->raw()
                    ],
                    $this->connection
                );
        } elseif ((new FromRoundRegistrationQuestion($this->answeredQuestion))->equals(new FromPure(new SpecificAreaChoosing()))) {
            return
                new SingleMutating(
                    <<<q
update meeting_round_participant
set interested_in_as_plain_text = ?
from meeting_round_invitation mri
where mri.user_id = meeting_round_participant.user_id and mri.meeting_round_id = meeting_round_participant.meeting_round_id and mri.id = ?
q
                    ,
                    [$this->userMessage->value(), $this->invitationId->value()->pure()->raw()],
                    $this->connection
                );
        }

        throw new Exception(sprintf('Unknown interest given: %s', (new FromRoundRegistrationQuestion($this->answeredQuestion))->value()->pure()->raw()));
    }
}