<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\WriteModel;

use Exception;
use TG\Domain\BotUser\Preference\Single\Pure\FromWhatDoYouPreferQuestionOptionName;
use TG\Domain\BotUser\Preference\Single\Pure\FromWhatIsYourGenderQuestionOptionName;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\FromString as WhatDoYouPreferOptionNameFromString;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\FromString as WhatIsYourGenderOptionNameFromString;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class Persistent implements RegistrationQuestionAnswer
{
    private $telegramUserId;
    private $userReply;
    private $registrationQuestionId;
    private $connection;

    public function __construct(InternalTelegramUserId $telegramUserId, UserMessage $userReply, RegistrationQuestion $registrationQuestionId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->userReply = $userReply;
        $this->registrationQuestionId = $registrationQuestionId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        if (!$this->registrationQuestionId->id()->isSuccessful()) {
            return $this->registrationQuestionId->id();
        }

        switch ($this->registrationQuestionId->id()->pure()->raw()) {
            case (new WhatDoYouPrefer())->id():
                return $this->savePreferences();

            case (new WhatIsYourGender())->id():
                return $this->saveGender();

            case (new AreYouReadyToRegister())->id():
                return $this->register();
        }

        throw new Exception(sprintf('Unknown question id = %s', $this->registrationQuestionId->id()->pure()->raw()));
    }

    private function savePreferences(): ImpureValue
    {
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
    }

    private function saveGender(): ImpureValue
    {
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

    private function register(): ImpureValue
    {
        return
            (new SingleMutating(
                'update bot_user set status = ? where telegram_id = ?',
                [
                    (new Registered())->value(),
                    $this->telegramUserId->value()
                ],
                $this->connection
            ))
                ->response();
    }
}