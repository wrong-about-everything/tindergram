<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\MessageToUser;

use TG\Domain\ABTesting\Impure\FromBotUser;
use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\RegistrationAnswerOption\Multiple\Impure\FromRegistrationQuestion;
use TG\Domain\RegistrationAnswerOption\Multiple\Pure\FromImpure;
use TG\Domain\RegistrationQuestion\Single\Impure\NextRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\FromImpure as PureRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Impure\FromPure;
use TG\Domain\RegistrationQuestion\Single\Pure\AreYouReadyToRegister;
use TG\Domain\TelegramBot\KeyboardButtons\KeyboardFromAnswerOptions;
use TG\Infrastructure\ABTesting\Pure\FromImpure as PureVariantIdFromImpure;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithKeyboard;
use TG\Infrastructure\TelegramBot\SentReplyToUser\MessageSentToUser;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FirstN;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromTelegram;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\UserAvatarIds;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;

class NextRegistrationQuestionSentToUser implements MessageSentToUser
{
    private $internalTelegramUserId;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->internalTelegramUserId = $internalTelegramUserId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $botUser = new ByInternalTelegramUserId($this->internalTelegramUserId, $this->connection);
        $nextRegistrationQuestion = new NextRegistrationQuestion($botUser);
        if (!$nextRegistrationQuestion->id()->isSuccessful()) {
            return $nextRegistrationQuestion->id();
        }

        if ($nextRegistrationQuestion->equals(new FromPure(new AreYouReadyToRegister()))) {
            return $this->areYouReadyToRegister($nextRegistrationQuestion, $botUser);
        } else {
            return
                (new DefaultWithKeyboard(
                    $this->internalTelegramUserId,
                    new FromQuestion(new PureRegistrationQuestion($nextRegistrationQuestion)),
                    new KeyboardFromAnswerOptions(
                        new FromImpure(
                            new FromRegistrationQuestion(
                                new NextRegistrationQuestion($botUser)
                            )
                        )
                    ),
                    $this->httpTransport
                ))
                    ->value();
        }
    }

    private function areYouReadyToRegister(RegistrationQuestion $nextRegistrationQuestion, BotUser $botUser): ImpureValue
    {
        $firstFiveAvatars = $this->firstFiveAvatars();
        if (!$firstFiveAvatars->value()->isSuccessful()) {
            return $firstFiveAvatars->value();
        }
        if (empty($firstFiveAvatars->value()->pure()->raw())) {
            return $this->isUserWithoutAvatarsReadyToRegister($nextRegistrationQuestion);
        }

        return $this->isUserWithAvatarsReadyToRegister($firstFiveAvatars, $nextRegistrationQuestion, $botUser);
    }

    private function isUserWithoutAvatarsReadyToRegister(RegistrationQuestion $nextRegistrationQuestion): ImpureValue
    {
        return
            (new DefaultWithKeyboard(
                $this->internalTelegramUserId,
                new QuestionToUserWithoutAvatarsWhetherHeIsReadyToRegister(),
                new KeyboardFromAnswerOptions(
                    new FromImpure(
                        new FromRegistrationQuestion(
                            $nextRegistrationQuestion
                        )
                    )
                ),
                $this->httpTransport
            ))
                ->value();
    }

    private function isUserWithAvatarsReadyToRegister(UserAvatarIds $firstFiveAvatars, RegistrationQuestion $nextRegistrationQuestion, BotUser $botUser): ImpureValue
    {
        $sentToUserResult =
            (new SentToUser(
                new VariantDependentQuestionToUserWithAvatarsWhetherHeIsReadyToRegister(
                    new PureVariantIdFromImpure(
                        new FromBotUser($botUser)
                    )
                ),
                $firstFiveAvatars,
                $this->internalTelegramUserId,
                $this->httpTransport
            ))
                ->value();
        if (!$sentToUserResult->isSuccessful()) {
            return $sentToUserResult;
        }

        return
            (new DefaultWithKeyboard(
                $this->internalTelegramUserId,
                new FromString('Ну что, поехали?'),
                new KeyboardFromAnswerOptions(
                    new FromImpure(
                        new FromRegistrationQuestion(
                            $nextRegistrationQuestion
                        )
                    )
                ),
                $this->httpTransport
            ))
                ->value();
    }

    private function firstFiveAvatars(): UserAvatarIds
    {
        return
            new FirstN(
                new FromTelegram(
                    $this->internalTelegramUserId,
                    $this->httpTransport
                ),
                5
            );
    }
}