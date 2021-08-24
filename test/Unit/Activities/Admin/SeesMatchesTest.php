<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Admin;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\OneMinute;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\Admin\SeesMatches;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use TG\Domain\Participant\Status\Pure\Registered;
use TG\Domain\UserInterest\InterestId\Pure\Single\Networking;
use TG\Domain\UserInterest\InterestName\Pure\DayDreaming;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\MeetingRound;
use TG\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;

class SeesMatchesTest extends TestCase
{
    public function testWhenNoMeetingRoundAheadThenEmptyResponseWillBeReturned()
    {
        $connection = new ApplicationConnection();
        $response = (new SeesMatches($this->botId(), $connection, new DevNull()))->response();
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals([], $response->body()->raw());
    }

    public function testWhenThereIsAMeetingRoundAheadWithSomeParticipantsThenNonEmptyResponseWillBeReturned()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneMinute()), $connection);
        $this->createParticipantVasya($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantPolina($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantFedya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);
        $this->createParticipantTolya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);

        $response = (new SeesMatches($this->botId(), $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertNotEmpty($response->body()->raw());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();;
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('3569d79b-1f96-4f04-abbf-e51c7848d4bf'));
    }

    private function meetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('8a998d04-91aa-4aed-bf85-c757e35df4fc');
    }

    private function createBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value()]
            ]);
    }

    private function createRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value()]
            ]);
    }

    private function createParticipantVasya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Vasya', 'last_name' => 'Belov', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@vasya',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }

    private function createParticipantFedya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Fedya', 'last_name' => 'Liubitel katatsya na velosipede', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@fedya',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }

    private function createParticipantTolya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Tolya', 'last_name' => 'Liubitel alkogolya', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@tolya',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }

    private function createParticipantPolina(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Polina', 'last_name' => 'P.', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@polzzzza',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }
}