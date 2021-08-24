<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron\InvitesToTakePartInANewRound;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NHours;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use TG\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\FromString as RoundId;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use TG\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use TG\Domain\Participant\Status\Impure\FromPure;
use TG\Domain\Participant\Status\Impure\FromReadModelParticipant;
use TG\Domain\Participant\Status\Pure\Registered;
use TG\Domain\RoundInvitation\ReadModel\ByMeetingRoundIdAndUserId;
use TG\Domain\RoundInvitation\Status\Impure\FromInvitation;
use TG\Domain\RoundInvitation\Status\Impure\FromPure as ImpureInvitationStatusFromPure;
use TG\Domain\RoundInvitation\Status\Pure\_New;
use TG\Domain\RoundInvitation\Status\Pure\Accepted;
use TG\Domain\RoundInvitation\Status\Pure\FromInteger;
use TG\Domain\RoundInvitation\Status\Pure\Sent;
use TG\Domain\RoundInvitation\Status\Pure\Status;
use TG\Domain\TelegramUser\ByTelegramId;
use TG\Domain\TelegramUser\UserId\FromTelegramUser;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\UserCommand\Start;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger as TelegramUserIdFromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\Fixed;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\MeetingRound;
use TG\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;
use TG\Activities\Cron\InvitesToTakePartInANewRound\InvitesToTakePartInANewRound;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use TG\UserActions\PressesStart\PressesStart;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class InvitesToTakePartInANewRoundTest extends TestCase
{
    public function testWhenAllMeetingInvitationsAreSentThenNoInvitationIsSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedSentMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(0, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testWhenNoneOfMeetingInvitationsAreSentThenTheFirst100InvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testGivenTwoRoundsWhenNoneOfInvitationsAreSentThenTheFirst100InvitationsAreSentForTheMeetingWithMatchingInvitationDate()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->botId(), new Future(new Now(), new NHours(1)), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testWhenSomeOfMeetingInvitationsAreSentThenTheRestOfInvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->secondUserId(), new _New(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testWhenSomeParticipantsAreRegisteredDuringRegistrationInBotThenInvitationsAreSentOnlyToNonParticipants()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->firstUserId(), new _New(), $connection);
        $this->newUserRegistersInBotAndForANewRound($this->secondTelegramUserId(), $connection);
        $this->assertUserIsARoundParticipantWithAcceptedInvitation($this->meetingRoundId(), new FromTelegramUser(new ByTelegramId($this->secondTelegramUserId(), $connection)), $connection);

        $transport = new Indifferent();
        (new InvitesToTakePartInANewRound($this->botId(), $transport, $connection, new DevNull()))->response();
        $this->assertCount(1, $transport->sentRequests());
        $this->assertInvitationIsSent($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function newUserRegistersInBotAndForANewRound(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $transport = new Indifferent();
        $this->newUserRegistersInBot($telegramUserId, $transport, $connection);
        $this->assertEquals(
            <<<q
Спасибо за ответы!

У нас уже намечаются встречи, готовы поучаствовать? Пришлю вам пару сегодня, а когда и как встретиться, онлайн или оффлайн, договоритесь между собой.
q
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $this->newUserAcceptsAnInvitationForARoundWithoutRegistrationQuestionAndGetsRegisteredRightAway($telegramUserId, $transport, $connection);
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
    }

    private function newUserRegistersInBot(InternalTelegramUserId $telegramUserId, HttpTransport $transport, OpenConnection $connection)
    {
        (new PressesStart(
            (new UserMessage($telegramUserId, (new Start())->value()))->value(),
            $this->botId()->value(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function newUserAcceptsAnInvitationForARoundWithoutRegistrationQuestionAndGetsRegisteredRightAway(InternalTelegramUserId $telegramUserId, HttpTransport $transport, OpenConnection $connection)
    {
        (new SendsArbitraryMessage(
            new Now(),
            (new UserMessage($telegramUserId, (new Sure())->value()))->value(),
            $this->botId()->value(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function secondTelegramUserId(): InternalTelegramUserId
    {
        return new TelegramUserIdFromInteger(231654987);
    }

    private function seedUser(TelegramUserId $userId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId->value(), 'telegram_id' => mt_rand(1, 999999)]
            ]);
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(),]
            ]);
    }

    private function seedMeetingRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $invitationDate, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'invitation_date' => $invitationDate->value(), 'start_date' => (new Future(new Now(), new NHours(2)))->value()]
            ]);
    }

    private function seedSentMeetingRoundInvitations(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new Sent())->value()],
            ]);
    }

    private function seedNewMeetingRoundInvitations(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new _New())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new _New())->value()],
            ]);
    }

    private function seedInvitation(MeetingRoundId $meetingRoundId, TelegramUserId $userId, Status $status, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $userId->value(), 'status' => $status->value()],
            ]);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function someOtherBotId(): BotId
    {
        return new FromUuid(new FromString('6ad926cc-6956-457e-a44d-bae2064263e2'));
    }

    private function meetingRoundId(): MeetingRoundId
    {
        return new RoundId('a49926cc-6956-457e-a44d-bae206426a8c');
    }

    private function someOtherMeetingRoundId(): MeetingRoundId
    {
        return new RoundId('b5d926cc-6956-457e-a44d-bae206426d98');
    }

    private function firstUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('5fe926cc-6956-457e-a44d-bae206426d1f'));
    }

    private function secondUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('bfd294ba-18f6-4dc0-ab35-8dc90ac4475b'));
    }

    private function assertAllInvitationsAreSent(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new Sent()));
            },
            (new Selecting(
                <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
where mr.id = ?
q
                ,
                [$meetingRoundId->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertAllInvitationsAreNew(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new _New()));
            },
            (new Selecting(
                <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
where mr.id = ?
q
                ,
                [$meetingRoundId->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertUserIsARoundParticipantWithAcceptedInvitation(MeetingRoundId $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
    {
        $participant =
            new ByMeetingRoundAndUser(
                $meetingRoundId,
                $userId,
                $connection
            );
        $this->assertTrue($participant->exists()->pure()->raw());
        $this->assertTrue(
            (new FromReadModelParticipant($participant))
                ->equals(
                    new FromPure(new Registered())
                )
        );
        $this->assertTrue(
            (new FromInvitation(
                new ByMeetingRoundIdAndUserId($meetingRoundId, $userId, $connection)
            ))
                ->equals(new ImpureInvitationStatusFromPure(new Accepted()))
        );
    }

    private function assertInvitationIsSent(MeetingRoundId $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromInvitation(
                new ByMeetingRoundIdAndUserId($meetingRoundId, $userId, $connection)
            ))
                ->equals(new ImpureInvitationStatusFromPure(new Sent()))
        );
    }
}