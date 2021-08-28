<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Funktion\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\RegistrationQuestion\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestionAnswer\AnswerTo;
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
        return (new AnswerTo($registrationQuestion, $this->botUser))->exists();
    }
}