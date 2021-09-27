<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestionAnswer\WriteModel;

use Exception;
use Meringue\Timeline\Point\Now;
use TG\Domain\Gender\Pure\FromWhatDoYouPreferQuestionOptionName;
use TG\Domain\Gender\Pure\FromWhatIsYourGenderQuestionOptionName;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\FromString as WhatDoYouPreferOptionNameFromString;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\FromString as WhatIsYourGenderOptionNameFromString;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatIsYourGender;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstNNonDeleted;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\UserAvatarIds;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class Persistent implements RegistrationQuestionAnswer
{
    private $telegramUserId;
    private $userReply;
    private $registrationQuestionId;
    private $transport;
    private $connection;

    public function __construct(
        InternalTelegramUserId $telegramUserId,
        UserMessage $userReply,
        RegistrationQuestion $registrationQuestionId,
        HttpTransport $transport,
        OpenConnection $connection
    )
    {
        $this->telegramUserId = $telegramUserId;
        $this->userReply = $userReply;
        $this->registrationQuestionId = $registrationQuestionId;
        $this->transport = $transport;
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
                'update bot_user set preferred_gender = ? where telegram_id = ?',
                [
                    (new FromWhatDoYouPreferQuestionOptionName(
                        new WhatDoYouPreferOptionNameFromString($this->userReply->value())
                    ))
                        ->value(),
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
        $userAvatars = $this->userAvatars()->value();

        return
            (new SingleMutating(
                'update bot_user set status = ?, registered_at = ?, has_avatar = ? where telegram_id = ?',
                [
                    (new Registered())->value(),
                    (new Now())->value(),
                    $userAvatars->isSuccessful()
                        ? (count($userAvatars->pure()->raw()) > 0 ? 1 : 0)
                        : 0,
                    $this->telegramUserId->value()
                ],
                $this->connection
            ))
                ->response();
    }

    private function userAvatars(): UserAvatarIds
    {
        return
            new FirstNNonDeleted(
                $this->telegramUserId,
                new FromTelegram(
                    $this->telegramUserId,
                    $this->transport
                ),
                5,
                $this->transport
            );
    }
}