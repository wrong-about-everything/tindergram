<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NHours;
use Meringue\ISO8601Interval\Floating\OneDay;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\BooleanAnswer\BooleanAnswerName\NoMaybeNextTime;
use TG\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use TG\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use TG\Domain\Participant\Status\Impure\FromReadModelParticipant;
use TG\Domain\Participant\Status\Impure\FromPure;
use TG\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use TG\Domain\Participant\Status\Pure\RegistrationInProgress;
use TG\Domain\Participant\Status\Pure\Status;
use TG\Domain\RoundInvitation\ReadModel\LatestInvitation;
use TG\Domain\RoundInvitation\Status\Impure\FromInvitation;
use TG\Domain\RoundInvitation\Status\Impure\FromPure as ImpureStatusFromPure;
use TG\Domain\RoundInvitation\Status\Pure\Accepted;
use TG\Domain\RoundInvitation\Status\Pure\Declined;
use TG\Domain\RoundInvitation\Status\Pure\Sent;
use TG\Domain\RoundInvitation\Status\Pure\Status as InvitationStatus;
use TG\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use TG\Domain\RoundRegistrationQuestion\Type\Pure\RoundRegistrationQuestionType;
use TG\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\Fixed;
use TG\Infrastructure\Uuid\FromString;
use TG\Infrastructure\Uuid\RandomUUID;
use TG\Infrastructure\Uuid\UUID as InfrastructureUUID;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\MeetingRound;
use TG\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use TG\Tests\Infrastructure\Stub\Table\RoundRegistrationQuestion;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;
use TG\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserAcceptsOrDeclinesRoundInvitationTest extends TestCase
{
    public function testWhenUserDeclinesRoundInvitationThenInvitationBecomesDeclinedAndHeSeesSeeYouNextTimeMessage()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new NoMaybeNextTime())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertInvitationIsDeclined($this->firstTelegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хорошо, тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantDoesNotExist($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    public function testGivenMeetingRoundHasNoParticipantsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeBecomesAParticipantWithRegistrationInProgressStatusAndHeSeesTheFirstRoundRegistrationQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'how r u?', $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'how r u?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new RegistrationInProgress());
    }

    public function testGivenMeetingRoundHasSomeParticipantsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeBecomesAParticipantWithRegistrationInProgressStatusAndHeSeesTheFirstRoundRegistrationQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createTelegramUser($this->secondUserId(), $this->secondTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->secondUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->secondUserId(), new Accepted(), $connection);
        $this->createRoundRegistrationQuestion($this->registrationQuestionId(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'Хотите просто поболтать?', $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new SpecificAreaChoosing(), 2, 'Что вас интересует?', $connection);
        $this->createUserRegistrationProgress($this->registrationQuestionId(), $this->secondUserId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хотите просто поболтать?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new RegistrationInProgress());
    }

    public function testWhenUserAcceptsFirstInvitationForRoundWithoutQuestionsThenItBecomesAcceptedAndHeBecomesAParticipantWithRegisteredStatus()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createTelegramUser($this->secondUserId(), $this->secondTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->secondUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->pastMeetingRoundId(), $this->botId(), new Past(new Now(), new OneHour()), new Past(new Now(), new OneDay()), $connection);
        $this->createMeetingRoundInvitation($this->pastMeetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->createMeetingRoundInvitation($this->pastMeetingRoundId(), $this->secondUserId(), new Sent(), $connection);
        $transport = new Indifferent();

        $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Past(new Now(), new NHours(2)), $transport, $connection);

        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->pastMeetingRoundId(), $this->firstUserId(), $connection, new ParticipantRegistered());

        $this->userReplies($this->secondTelegramUserId(), (new Sure())->value(), new Past(new Now(), new NHours(2)), $transport, $connection);

        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->participantExists($this->pastMeetingRoundId(), $this->secondUserId(), $connection, new ParticipantRegistered());

        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->secondUserId(), new Sent(), $connection);

        $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Past(new Now(), new NHours(2)), $transport, $connection);

        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new ParticipantRegistered());

        $this->userReplies($this->secondTelegramUserId(), (new Sure())->value(), new Past(new Now(), new NHours(2)), $transport, $connection);

        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->secondUserId(), $connection, new ParticipantRegistered());
    }

    public function testGivenMeetingRoundHasNoRegistrationQuestionsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new ParticipantRegistered());
    }

    public function testGivenNoMeetingRoundsAheadWhenUserAcceptsInvitationThenHeSeesSorryAndSeeYouNextTime()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Past(new Now(), new OneHour()), new Now(), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new Sure())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Раунд встреч уже идёт или уже прошёл. Мы пришлём вам приглашение на новый раунд, как только о нём станет известно. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantDoesNotExist($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function userReplies(InternalTelegramUserId $who, string $what, ISO8601DateTime $when, HttpTransport $transport, OpenConnection $connection)
    {
        return
            (new SendsArbitraryMessage(
                $when,
                (new UserMessage($who, $what))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();
    }

    private function createBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
    }

    private function createTelegramUser(TelegramUserId $userId, InternalTelegramUserId $telegramUserId, $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $telegramUserId->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
    }

    private function createBotUser(BotId $botId, TelegramUserId $userId, UserStatus $status, $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $botId->value(), 'user_id' => $userId->value(), 'status' => $status->value()]
            ]);
    }

    private function createMeetingRound(string $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, ISO8601DateTime $invitationDateTime, $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId, 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value(), 'invitation_date' => $invitationDateTime->value()]
            ]);
    }

    private function createMeetingRoundInvitation(string $meetingRoundId, TelegramUserId $userId, InvitationStatus $status, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $userId->value(), 'status' => $status->value()]
            ]);
    }

    private function createRoundRegistrationQuestion(InfrastructureUUID $id, string $meetingRoundId, RoundRegistrationQuestionType $questionType, int $ordinalNumber, string $text, OpenConnection $connection)
    {
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => $id->value(), 'meeting_round_id' => $meetingRoundId, 'type' => $questionType->value(), 'ordinal_number' => $ordinalNumber, 'text' => $text]
            ]);
    }

    private function createUserRegistrationProgress(InfrastructureUUID $registrationQuestionId, TelegramUserId $userId, OpenConnection $connection)
    {
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $registrationQuestionId->value(), 'user_id' => $userId->value()]
            ]);

    }

    private function firstTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function secondTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(123456);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function meetingRoundId(): string
    {
        return 'e00729d6-330c-4123-b856-d5196812d111';
    }

    private function pastMeetingRoundId(): string
    {
        return 'a00641bf-d3e2-4d58-b959-a6f15d410bd0';
    }

    private function firstUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function secondUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('abc729d6-330c-4123-b856-d5196812ddef'));
    }

    private function registrationQuestionId(): InfrastructureUUID
    {
        return new FromString('36221907-1226-4bfb-9d09-64b119c6b0a4');
    }

    private function assertInvitationIsDeclined(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromInvitation(
                new LatestInvitation($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureStatusFromPure(new Declined())
                )
        );
    }

    private function participantExists(string $meetingRoundId, TelegramUserId $userId, OpenConnection $connection, Status $status)
    {
        $participant =
            new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            );
        $this->assertTrue($participant->exists()->pure()->raw());
        $this->assertTrue(
            (new FromReadModelParticipant($participant))
                ->equals(
                    new FromPure($status)
                )
        );
    }

    private function participantDoesNotExist(string $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            ))
                ->exists()->pure()->raw()
        );
    }
}