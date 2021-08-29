<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Multiple\Funktion\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestionAnswer\ReadModel\AnswerToRegistrationQuestionFromDatabase;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class QuestionIsAnswered implements RegistrationQuestionToBoolean
{
    private $botUser;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
    }

    public function __invoke(RegistrationQuestion $registrationQuestion): ImpureValue
    {
        return (new AnswerToRegistrationQuestionFromDatabase($registrationQuestion, $this->botUser))->exists();
    }
}