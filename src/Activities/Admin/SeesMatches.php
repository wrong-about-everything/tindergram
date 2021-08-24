<?php

declare(strict_types=1);

namespace TG\Activities\Admin;

use Meringue\Timeline\Point\Now;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromRound;
use TG\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use TG\Domain\Matches\ReadModel\Impure\Matches;
use TG\Domain\MeetingRound\ReadModel\LatestNotYetStarted;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\UserStory\Body\Arrray;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\NonRetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;

class SeesMatches extends Existent
{
    private $botId;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Admin sees matches scenario started'));

        $generatedMatchesForAllParticipants = $this->generatedMatchesForAllParticipants()->value();
        if (!$generatedMatchesForAllParticipants->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($generatedMatchesForAllParticipants));
            return new NonRetryableServerError(new Arrray(['error' => $generatedMatchesForAllParticipants->error()->logMessage()]));
        }

        $this->logs->receive(new InformationMessage('Admin sees matches scenario finished'));

        return
            new Successful(
                new Arrray(
                    $generatedMatchesForAllParticipants->pure()->isPresent()
                        ? $generatedMatchesForAllParticipants->pure()->raw()
                        : []
                )
            );
    }

    private function generatedMatchesForAllParticipants(): Matches
    {
        return
            new GeneratedMatchesForAllParticipants(
                new FromRound(
                    new LatestNotYetStarted($this->botId, new Now(), $this->connection),
                    $this->connection
                )
            );
    }
}