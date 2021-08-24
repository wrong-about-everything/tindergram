<?php

declare(strict_types=1);

namespace TG\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\Domain\FeedbackInvitation;

use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\FromBooleanAnswerName;
use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\No;
use TG\Domain\BooleanAnswer\BooleanAnswerId\Pure\Yes;
use TG\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use TG\Domain\BooleanAnswer\BooleanAnswerName\NoMaybeNextTime;
use TG\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use TG\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FeedbackInvitationId;
use TG\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FromFeedbackInvitation;
use TG\Domain\FeedbackInvitation\ReadModel\FeedbackInvitation;
use TG\Domain\FeedbackInvitation\WriteModel\FeedbackInvitation as WriteModelFeedbackInvitation;
use TG\Domain\FeedbackInvitation\ReadModel\NonSuccessful;
use TG\Domain\FeedbackInvitation\WriteModel\Accepted;
use TG\Domain\FeedbackInvitation\WriteModel\Declined;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class AcceptedOrDeclinedFeedbackInvitation implements WriteModelFeedbackInvitation
{
    private $message;
    private $feedbackInvitation;
    private $connection;
    private $concrete;

    public function __construct(array $message, FeedbackInvitation $invitation, OpenConnection $connection)
    {
        $this->message = $message;
        $this->feedbackInvitation = $invitation;
        $this->connection = $connection;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    private function concrete(): FeedbackInvitationId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): FeedbackInvitationId
    {
        $invitationId = new FromFeedbackInvitation($this->feedbackInvitation);

        if ($this->no()) {
            $declinedInvitationValue = (new Declined($invitationId, $this->connection))->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new FromFeedbackInvitation(new NonSuccessful($declinedInvitationValue));
            }
            return $invitationId;
        } elseif ($this->yes()) {
            $acceptedInvitationValue = (new Accepted($invitationId, $this->connection))->value();
            if (!$acceptedInvitationValue->isSuccessful()) {
                return new FromFeedbackInvitation(new NonSuccessful($acceptedInvitationValue));
            }
            return $invitationId;
        }

        return
            new FromFeedbackInvitation(
                new NonSuccessful(
                    new Failed(
                        new AlarmDeclineWithDefaultUserMessage(
                            'Feedback invitation answer is neither yes nor no. Either this user story was run by mistake or user replied with custom message instead of pushing a button.',
                            []
                        )
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
                new FromUserMessage(
                    new FromParsedTelegramMessage($this->message)
                )
            ))
                ->equals(new Yes());
    }
}