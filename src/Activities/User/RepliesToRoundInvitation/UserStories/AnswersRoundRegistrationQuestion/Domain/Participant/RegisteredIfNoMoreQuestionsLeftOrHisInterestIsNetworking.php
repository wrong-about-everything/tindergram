<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\Participant;

use TG\Domain\Participant\ParticipantId\Impure\FromReadModelParticipant;
use TG\Domain\Participant\ReadModel\Participant as RoundParticipant;
use TG\Domain\Participant\WriteModel\Participant;
use TG\Domain\UserInterest\InterestId\Pure\Single\Networking;
use TG\Domain\Participant\ReadModel\ByInvitationId;
use TG\Domain\Participant\WriteModel\Registered;
use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use TG\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use TG\Domain\UserInterest\InterestId\Impure\Single\FromPure;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class RegisteredIfNoMoreQuestionsLeftOrHisInterestIsNetworking implements Participant
{
    private $invitationId;
    private $connection;

    private $cached;

    public function __construct(InvitationId $invitationId, OpenConnection $connection)
    {
        $this->invitationId = $invitationId;
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
        $participant = new ByInvitationId($this->invitationId, $this->connection);
        if (
            !(new NextRoundRegistrationQuestion($this->invitationId, $this->connection))->value()->pure()->isPresent()
                ||
            $this->participantIsOnlyInterestedInNetworking($participant)
        ) {
            $registeredParticipant = (new Registered($this->invitationId, $this->connection))->value();
            if (!$registeredParticipant->isSuccessful()) {
                return $registeredParticipant;
            }
        }

        return (new FromReadModelParticipant($participant))->value();
    }

    private function participantIsOnlyInterestedInNetworking(RoundParticipant $participant)
    {
        return
            count((new FromParticipant($participant))->value()->pure()->raw()) === 1
                &&
            (new FromParticipant($participant))->contain(new FromPure(new Networking()))
            ;
    }
}