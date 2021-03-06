<?php

declare(strict_types=1);

namespace TG\Activities\User\RatesAPair;

use TG\Domain\BotUser\ReadModel\BotUser;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\ReadModel\NextCandidateFor;
use TG\Domain\BotUser\WriteModel\SwitchedToVisibleModeOrStayedTheSame;
use TG\Domain\Reaction\Pure\FromAction;
use TG\Domain\TelegramBot\InternalTelegramUserId\Impure\FromWriteModelBotUser;
use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;
use TG\Domain\Pair\WriteModel\SentIfExistsThatIsAllForNowOtherwise;
use TG\Domain\Reaction\Pure\FromViewedPair;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\TelegramBot\InlineAction\FromRateCallbackData;
use TG\Domain\TelegramBot\InlineAction\ThumbsUp;
use TG\Domain\TelegramBot\InternalTelegramUserId\Pure\PairTelegramIdFromRateCallback;
use TG\Domain\TelegramBot\MessageToUser\YouCanNotRateAUserMoreThanOnce;
use TG\Domain\TelegramBot\MessageToUser\YouHaveAMatch;
use TG\Domain\Pair\ReadModel\ByVoterTelegramIdAndRatedTelegramId;
use TG\Domain\Pair\ReadModel\Pair as ReadModelViewedPair;
use TG\Domain\Pair\WriteModel\Rated;
use TG\Domain\Pair\WriteModel\Pair;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\ErrorFromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\FromPure;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\SentReplyToUser\DefaultWithNoKeyboard;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class RatesAPair extends Existent
{
    private $voterTelegramId;
    private $rateCallbackData;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(InternalTelegramUserId $voterTelegramId, RateCallbackData $rateCallbackData, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->voterTelegramId = $voterTelegramId;
        $this->rateCallbackData = $rateCallbackData;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User rates a pair scenario started'));

        $voterBotUser = new ByInternalTelegramUserId($this->voterTelegramId, $this->connection);
        if ((new FromViewedPair($this->viewedPair()))->exists()) {
            $this->logs->receive(new InformationMessage('Someone rated a pair one more time'));
            $this->youCanNotRateAUserMoreThatOnce();
        } else {
            $persistentPair = $this->ratedPair($voterBotUser);
            if (!$persistentPair->value()->isSuccessful()) {
                $this->logs->receive(new FromNonSuccessfulImpureValue($persistentPair->value()));
            } else {
                if ($this->thereIsAMatch()) {
                    $this->sendContactsToEachOther($voterBotUser);
                }
            }
        }

        $value = $this->nextSentPairIfExists()->value();
        if (!$value->isSuccessful()) {
            $this->logs->receive(new ErrorFromNonSuccessfulImpureValue($value));
        }

        $this->logs->receive(new InformationMessage('User rates a pair scenario finished'));

        return new Successful(new Emptie());
    }

    private function viewedPair(): ReadModelViewedPair
    {
        return
            new ByVoterTelegramIdAndRatedTelegramId(
                $this->voterTelegramId,
                $this->pairTelegramId(),
                $this->connection
            );
    }

    private function youCanNotRateAUserMoreThatOnce(): ImpureValue
    {
        return
            (new DefaultWithNoKeyboard(
                $this->voterTelegramId,
                new YouCanNotRateAUserMoreThanOnce(),
                $this->transport
            ))
                ->value();
    }

    private function ratedPair(BotUser $voterBotUser): Pair
    {
        return
            new Rated(
                new FromWriteModelBotUser(
                    new SwitchedToVisibleModeOrStayedTheSame(
                        $voterBotUser,
                        new FromAction(new FromRateCallbackData($this->rateCallbackData)),
                        $this->logs,
                        $this->connection
                    )
                ),
                new FromPure($this->pairTelegramId()),
                new FromAction(new FromRateCallbackData($this->rateCallbackData)),
                $this->connection
            );
    }

    private function thereIsAMatch(): bool
    {
        $invertedPair = new ByVoterTelegramIdAndRatedTelegramId($this->pairTelegramId(), $this->voterTelegramId, $this->connection);

        return
            (new FromRateCallbackData($this->rateCallbackData))->equals(new ThumbsUp())
                &&
            (new FromViewedPair($invertedPair))->exists()
                &&
            (new FromViewedPair($invertedPair))->equals(new Like())
        ;
    }

    private function pairTelegramId(): InternalTelegramUserId
    {
        return new PairTelegramIdFromRateCallback($this->rateCallbackData);
    }

    private function sendContactsToEachOther(BotUser $voterBotUser)
    {
        $this->sendContactsToCurrentVoter();
        $this->sendContactsToPair($voterBotUser);
    }

    private function sendContactsToCurrentVoter(): void
    {
        $firstSentMessageValue =
            (new DefaultWithNoKeyboard(
                $this->voterTelegramId,
                new YouHaveAMatch(
                    (new ByInternalTelegramUserId(
                        $this->pairTelegramId(),
                        $this->connection
                    ))
                        ->value()->pure()->raw()['telegram_handle']
                ),
                $this->transport
            ))
                ->value();
        if (!$firstSentMessageValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($firstSentMessageValue));
        }
    }

    private function sendContactsToPair(BotUser $voterBotUser): void
    {
        $secondSentMessageValue =
            (new DefaultWithNoKeyboard(
                $this->pairTelegramId(),
                new YouHaveAMatch(
                    $voterBotUser->value()->pure()->raw()['telegram_handle']
                ),
                $this->transport
            ))
                ->value();
        if (!$secondSentMessageValue->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($secondSentMessageValue));
        }
    }

    private function nextSentPairIfExists(): Pair
    {
        return
            new SentIfExistsThatIsAllForNowOtherwise(
                new NextCandidateFor($this->voterTelegramId, $this->connection),
                $this->voterTelegramId,
                $this->transport,
                $this->connection
            );
    }
}