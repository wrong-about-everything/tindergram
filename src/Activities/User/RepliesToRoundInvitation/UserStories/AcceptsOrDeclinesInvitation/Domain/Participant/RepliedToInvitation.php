<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToRoundInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\Participant;

use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\FromBooleanAnswerName;
use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\No;
use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\Yes;
use TG\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use TG\Domain\Participant\WriteModel\AcceptedInvitation;
use TG\Domain\Participant\WriteModel\NonExistent;
use TG\Domain\Participant\WriteModel\NonSuccessful;
use TG\Domain\Participant\WriteModel\Participant;
use TG\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use TG\Domain\RoundInvitation\ReadModel\Invitation as ReadModelInvitation;
use TG\Domain\RoundInvitation\WriteModel\Accepted;
use TG\Domain\RoundInvitation\WriteModel\Declined;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class RepliedToInvitation implements Participant
{
    private $message;
    private $invitation;
    private $connection;
    private $concrete;

    public function __construct(array $message, ReadModelInvitation $invitation, OpenConnection $connection)
    {
        $this->message = $message;
        $this->invitation = $invitation;
        $this->connection = $connection;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    private function concrete(): Participant
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): Participant
    {
        $invitationId = new FromInvitation($this->invitation);

        if ($this->no()) {
            $declinedInvitationValue = (new Declined($invitationId, $this->connection))->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new NonSuccessful($declinedInvitationValue);
            }

            return new NonExistent();
        } elseif ($this->yes()) {
            $acceptedInvitationValue = (new Accepted($invitationId, $this->connection))->value();
            if (!$acceptedInvitationValue->isSuccessful()) {
                return new NonSuccessful($acceptedInvitationValue);
            }

            $participant = new AcceptedInvitation($invitationId, $this->connection);
            if (!$participant->value()->isSuccessful()) {
                return new NonSuccessful($participant->value());
            }

            return $participant;
        }

        return
            new NonSuccessful(
                new Failed(
                    new AlarmDeclineWithDefaultUserMessage(
                        'An invitation reply answer is neither yes nor no. Either this user story was run by mistake or user replied with custom message instead of pushing a button.',
                        []
                    )
                )
            );
    }

    private function no()
    {
        return
            (new FromBooleanAnswerName(
                new FromUserMessage(
                    new FromParsedTelegramMessage($this->message)
                )
            ))
                ->equals(new No());
    }

    private function yes()
    {
        return
            (new FromBooleanAnswerName(
                (new FromUserMessage(
                    new FromParsedTelegramMessage($this->message)
                ))
            ))
                ->equals(new Yes());
    }
}