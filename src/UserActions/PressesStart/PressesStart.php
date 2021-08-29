<?php

declare(strict_types=1);

namespace TG\UserActions\PressesStart;

use TG\Domain\BotUser\ReadModel\FromWriteModel;
use TG\Domain\SentReplyToUser\InCaseOfAnyUncertainty;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Impure\UserStatus;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Domain\SentReplyToUser\Sorry;
use TG\Domain\BotUser\WriteModel\AddedIfNotYet;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromParsedTelegramMessage;
use TG\Infrastructure\TelegramBot\MessageToUser\FillInYourUserNameAndFirstName;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
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
            $this->sorry()->value();
            return new Successful(new Emptie());
        }

        if ($userStatus->equals(new ImpureUserStatusFromPure(new RegistrationIsInProgress()))) {
            (new NonRegisteredUserPressesStart(
                $this->message,
                $this->httpTransport,
                $this->connection,
                $this->logs
            ))
                ->response();
        } elseif ($userStatus->equals(new ImpureUserStatusFromPure(new Registered()))) {
            $userIsAlreadyRegisteredValue = $this->replyInCaseOfAnyUncertainty()->value();
            if (!$userIsAlreadyRegisteredValue->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($userIsAlreadyRegisteredValue));
                $this->sorry()->value();
                return new Successful(new Emptie());
            }
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

    private function replyInCaseOfAnyUncertainty()
    {
        return
            new InCaseOfAnyUncertainty(
                new FromParsedTelegramMessage($this->message),
                $this->httpTransport
            );
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

    private function sorry()
    {
        return
            new Sorry(
                new FromParsedTelegramMessage($this->message),
                $this->httpTransport
            );
    }
}