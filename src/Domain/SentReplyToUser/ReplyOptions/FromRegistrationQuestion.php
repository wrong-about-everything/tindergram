<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser\ReplyOptions;

use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Experience\AvailableExperiences\ByBotId as AvailableExperiences;
use TG\Domain\Experience\ExperienceId\Pure\FromInteger as ExperienceFromInteger;
use TG\Domain\Experience\ExperienceName\FromExperience;
use TG\Domain\Position\AvailablePositions\ByBotId as AvailablePositions;
use TG\Domain\Position\PositionId\Pure\FromInteger;
use TG\Domain\Position\PositionName\FromPosition;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestionType\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestionType\Impure\FromRegistrationQuestion as RegistrationQuestionType;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestionType\Pure\About;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestionType\Pure\Position;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Domain\TelegramBot\UserMessage\Pure\Skipped;

/**
 * @deprecated
 */
class FromRegistrationQuestion implements ReplyOptions
{
    private $registrationQuestion;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(RegistrationQuestion $registrationQuestion, BotId $botId, OpenConnection $connection)
    {
        $this->registrationQuestion = $registrationQuestion;
        $this->botId = $botId;
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

        if ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Position()))) {
            return new Successful(new Present($this->twoPositionsInARow()));
        } elseif ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Experience()))) {
            return new Successful(new Present($this->twoExperiencesInARow()));
        } elseif ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new About()))) {
            return new Successful(new Present($this->skip()));
        }

        return new Successful(new Present([]));
    }

    private function twoPositionsInARow()
    {
        return
            array_reduce(
                (new AvailablePositions($this->botId, $this->connection))->value()->pure()->raw(),
                function (array $carry, int $position) {
                    if (empty($carry) || count($carry[count($carry) - 1]) === 2) {
                        $carry[] = [['text' => (new FromPosition(new FromInteger($position)))->value()]];
                    } else {
                        $carry[count($carry) - 1][] = ['text' => (new FromPosition(new FromInteger($position)))->value()];
                    }

                    return $carry;
                },
                []
            );
    }

    private function twoExperiencesInARow()
    {
        return
            array_reduce(
                (new AvailableExperiences($this->botId, $this->connection))->value()->pure()->raw(),
                function (array $carry, int $experience) {
                    if (empty($carry) || count($carry[count($carry) - 1]) === 2) {
                        $carry[] = [['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()]];
                    } else {
                        $carry[count($carry) - 1][] = ['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()];
                    }

                    return $carry;
                },
                []
            );
    }

    private function skip()
    {
        return [[['text' => (new Skipped())->value()]]];
    }
}