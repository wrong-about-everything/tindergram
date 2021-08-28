<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\RegistrationQuestions\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\RegistrationQuestion\RegistrationQuestions\Funktion\Impure\QuestionIsAnswered;
use TG\Domain\RegistrationQuestion\RegistrationQuestions\Pure\RegistrationQuestions as PureRegistrationQuestions;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class Unanswered implements RegistrationQuestions
{
    private $pureRegistrationQuestions;
    private $botUser;

    public function __construct(PureRegistrationQuestions $pureRegistrationQuestions, BotUser $botUser)
    {
        $this->pureRegistrationQuestions = $pureRegistrationQuestions;
        $this->botUser = $botUser;
    }

    public function value(): ImpureValue
    {
        return
            (new FilteredOut(
                $this->pureRegistrationQuestions,
                new QuestionIsAnswered($this->botUser)
            ))
                ->value();
    }
}