<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion as PureRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Multiple\Impure\Unanswered;
use TG\Domain\RegistrationQuestion\Multiple\Pure\AllQuestions;
use TG\Domain\RegistrationQuestion\Multiple\Impure\Ordered;
use TG\Infrastructure\ImpureInteractions\ImpureValue;

class NextRegistrationQuestion extends RegistrationQuestion
{
    private $botUser;
    private $cached;

    public function __construct(BotUser $botUser)
    {
        $this->botUser = $botUser;
        $this->cached = null;
    }

    public function id(): ImpureValue
    {
        return $this->concrete()->id();
    }

    public function ordinalNumber(): ImpureValue
    {
        return $this->concrete()->ordinalNumber();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestion
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): RegistrationQuestion
    {
        return
            new First(
                new Ordered(
                    new Unanswered(
                        new AllQuestions(),
                        $this->botUser
                    ),
                    function (PureRegistrationQuestion $left, PureRegistrationQuestion $right) {
                        return $left->ordinalNumber() > $right->ordinalNumber();
                    }
                )
            );
    }
}