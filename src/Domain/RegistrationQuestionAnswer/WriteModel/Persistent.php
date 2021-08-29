<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\WriteModel;

use TG\Domain\BotUser\Preference\Single\Pure\FromWhatDoYouPreferQuestionOptionName;
use TG\Domain\BotUser\Preference\Single\Pure\FromWhatIsYourGenderQuestionOptionName;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\FromString as WhatDoYouPreferOptionNameFromString;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\FromString as WhatIsYourGenderOptionNameFromString;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\FromRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatDoYouPreferId;
use TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure\WhatIsYourGenderId;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class Persistent implements RegistrationQuestionAnswer
{
    private $telegramUserId;
    private $userReply;
    private $registrationQuestion;
    private $connection;

    public function __construct(InternalTelegramUserId $telegramUserId, UserMessage $userReply, RegistrationQuestion $registrationQuestion, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->userReply = $userReply;
        $this->registrationQuestion = $registrationQuestion;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        switch ((new FromRegistrationQuestion(new FromImpure($this->registrationQuestion)))->value()) {
            case (new WhatDoYouPreferId())->value():
                return
                    (new SingleMutating(
                        'update bot_user set preferences = ? where telegram_id = ?',
                        [
                            json_encode([
                                (new FromWhatDoYouPreferQuestionOptionName(
                                    new WhatDoYouPreferOptionNameFromString($this->userReply->value())
                                ))
                                    ->value()
                            ]),
                            $this->telegramUserId->value()
                        ],
                        $this->connection
                    ))
                        ->response();

            case (new WhatIsYourGenderId())->value():
                return
                    (new SingleMutating(
                        'update bot_user set gender = ? where telegram_id = ?',
                        [
                            (new FromWhatIsYourGenderQuestionOptionName(
                                new WhatIsYourGenderOptionNameFromString($this->userReply->value())
                            ))
                                ->value(),
                            $this->telegramUserId->value()
                        ],
                        $this->connection
                    ))
                        ->response();
        }
    }
}