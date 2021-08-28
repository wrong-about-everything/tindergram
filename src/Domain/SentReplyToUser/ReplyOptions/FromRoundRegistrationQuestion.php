<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser\ReplyOptions;

use TG\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use TG\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use TG\Domain\RoundRegistrationQuestion\Type\Impure\FromPure;
use TG\Domain\RoundRegistrationQuestion\Type\Impure\FromRoundRegistrationQuestion as RoundRegistrationQuestionType;
use TG\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use TG\Domain\UserInterest\InterestId\Impure\Multiple\AvailableInterestIdsInRoundByInvitationId;
use TG\Domain\UserInterest\InterestId\Pure\Single\FromInteger;
use TG\Domain\UserInterest\InterestName\Pure\FromInterestId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

/**
 * @deprecated
 */
class FromRoundRegistrationQuestion implements ReplyOptions
{
    private $registrationQuestion;
    private $invitationId;
    private $connection;
    private $cached;

    public function __construct(RoundRegistrationQuestion $roundRegistrationQuestion, InvitationId $invitationId, OpenConnection $connection)
    {
        $this->registrationQuestion = $roundRegistrationQuestion;
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
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        if ((new RoundRegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new NetworkingOrSomeSpecificArea()))) {
            return
                new Successful(
                    new Present(
                        array_map(
                            function (int $interest) {
                                return [['text' => (new FromInterestId(new FromInteger($interest)))->value()]];
                            },
                            (new AvailableInterestIdsInRoundByInvitationId($this->invitationId, $this->connection))->value()->pure()->raw()
                        )
                    )
                );
        }

        return new Successful(new Present([]));
    }
}