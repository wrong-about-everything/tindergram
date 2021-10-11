<?php

declare(strict_types=1);

namespace TG\UserActions\PressesStart;

use TG\Domain\BotUser\ReadModel\FromWriteModel;
use TG\Domain\BotUser\ReadModel\NextCandidateFor;
use TG\Domain\BotUser\UserStatus\Pure\InactiveAfterRegistered;
use TG\Domain\BotUser\UserStatus\Pure\InactiveBeforeRegistered;
use TG\Domain\BotUser\WriteModel\TurnedActiveFromInactive;
use TG\Domain\Pair\WriteModel\SentIfExistsThatIsAllForNowOtherwise;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Impure\UserStatus;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromWriteModelBotUser;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Domain\BotUser\WriteModel\AddedIfNotYet;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\FromPure;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromImpure;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\MessageToUser\FillInYourUserNameAndFirstName;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\NonRetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Activities\User\RegistersInBot\UserStories\NonRegisteredUserPressesStart\NonRegisteredUserPressesStart;

class PressesStart extends Existent
{
    private $message;
    private $httpTransport;
    private $connection;
    private $logs;

    public function __construct(array $message, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->message = $message;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User presses start scenario started'));

        if ($this->usernameIsEmpty()) {
            $this->fillInYourUsernameAndFirstName()->value();
            return new Successful(new Emptie());
        }

        $userStatus = $this->userStatus();
        if (!$userStatus->value()->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($userStatus->value()));
            return new NonRetryableServerError(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            (new NonRegisteredUserPressesStart(
                new FromParsedTelegramMessage($this->message),
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $nextPairValue =
                $this->nextPairIfExists(
                    new FromPure(
                        new FromParsedTelegramMessage($this->message)
                    )
                );
            if (!$nextPairValue->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($nextPairValue));
                return new NonRetryableServerError(new Emptie());
            }
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new InactiveAfterRegistered()))) {
            $nextPairValue =
                $this->welcomeBack(
                    new FromWriteModelBotUser(
                        new TurnedActiveFromInactive(new FromParsedTelegramMessage($this->message), $this->connection)
                    )
                );
            if (!$nextPairValue->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($nextPairValue));
                return new NonRetryableServerError(new Emptie());
            }
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new InactiveBeforeRegistered()))) {
            (new NonRegisteredUserPressesStart(
                new FromImpure(
                    new FromWriteModelBotUser(
                        new TurnedActiveFromInactive(
                            new FromParsedTelegramMessage($this->message),
                            $this->connection
                        )
                    )
                ),
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
        }

        $this->logs->receive(new InformationMessage('User presses start scenario finished'));

        return new Successful(new Emptie());
    }

    private function usernameIsEmpty(): bool
    {
        return
            !isset($this->message['message']['from']['username'])
                ||
            empty($this->message['message']['from']['username']);
    }

    private function userStatus(): UserStatus
    {
        return
            new FromBotUser(
                new FromWriteModel(
                    new AddedIfNotYet(
                        new FromParsedTelegramMessage($this->message),
                        $this->message['message']['from']['first_name'],
                        $this->message['message']['from']['last_name'] ?? '',
                        $this->message['message']['from']['username'],
                        $this->connection
                    ),
                    $this->connection
                )
            );
    }

    private function nextPairIfExists(InternalTelegramUserId $forUser): ImpureValue
    {
        return
            (new SentIfExistsThatIsAllForNowOtherwise(
                new NextCandidateFor(
                    new FromParsedTelegramMessage($this->message),
                    $this->connection
                ),
                new FromImpure($forUser),
                $this->httpTransport,
                $this->connection
            ))
                ->value();
    }

    private function welcomeBack(InternalTelegramUserId $forUser): ImpureValue
    {
        $welcomeBack =
            (new DefaultWithNoKeyboard(
                new FromImpure($forUser),
                new FromString('Ваш аккаунт снова активен!'),
                $this->httpTransport
            ))
                ->value();
        if (!$welcomeBack->isSuccessful()) {
            return $welcomeBack;
        }

        return
            (new SentIfExistsThatIsAllForNowOtherwise(
                new NextCandidateFor(
                    new FromParsedTelegramMessage($this->message),
                    $this->connection
                ),
                new FromImpure($forUser),
                $this->httpTransport,
                $this->connection
            ))
                ->value();
    }

    private function fillInYourUsernameAndFirstName()
    {
        return
            new DefaultWithNoKeyboard(
                new FromParsedTelegramMessage($this->message),
                new FillInYourUserNameAndFirstName(),
                $this->httpTransport
            );
    }
}