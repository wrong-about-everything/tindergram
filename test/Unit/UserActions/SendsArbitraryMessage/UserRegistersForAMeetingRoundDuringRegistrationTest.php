<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\ISO8601Interval\Floating\OneDay;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\BotUser\ByTelegramUserId;
use TG\Domain\Experience\ExperienceName\LessThanAYearName;
use TG\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use TG\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use TG\Domain\Participant\Status\Impure\FromPure;
use TG\Domain\Participant\Status\Impure\FromReadModelParticipant;
use TG\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use TG\Domain\Participant\Status\Pure\Status;
use TG\Domain\Position\PositionId\Pure\ProductDesigner;
use TG\Domain\Position\PositionId\Pure\ProductManager;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\FromString as RegistrationQuestionIdFromString;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\RegistrationQuestionType;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser as UserStatusFromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Domain\UserInterest\InterestId\Pure\Single\Networking;
use TG\Domain\UserInterest\InterestId\Pure\Single\SpecificArea;
use TG\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
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
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\MeetingRound;
use TG\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;
use TG\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserRegistersForAMeetingRoundDuringRegistrationTest extends TestCase
{
    public function testGivenMeetingRoundAheadWithNoRoundRegistrationQuestionWhenUserAnswersTheLastRegistrationQuestionThenHeSeesAnInvitationToAMeetingRoundAndAcceptsItAndBecomesRegisteredParticipant()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new Experience(), $this->botId(), 2, 'А опыт?', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionId(), $this->userId(), $connection);
        $this->createMeetingRound(Uuid::uuid4()->toString(), $this->botId(), new Past(new Now(), new OneDay()), new Past(new Now(), new NDays(2)), $connection);
        $this->createMeetingRound($this->futureMeetingRoundId(), $this->botId(), new FromISO8601('2025-08-08T09:00:00+03'), new Now(), $connection);
        $transport = new Indifferent();

        $registrationResponse = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($registrationResponse->isSuccessful());
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            <<<q
Спасибо за ответы!

У нас уже намечаются встречи, готовы поучаствовать? Пришлю вам пару 8 августа (это пятница), а когда и как встретиться, онлайн или оффлайн, договоритесь между собой.
q
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $this->userReply((new Sure())->value(), $transport, $connection)->response();
        $this->participantExists($this->futureMeetingRoundId(), $this->userId(), $connection, new ParticipantRegistered());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function createRegistrationQuestion(RegistrationQuestionId $registrationQuestionId, RegistrationQuestionType $questionType, BotId $botId, int $ordinalNumber, string $text, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $registrationQuestionId->value()->pure()->raw(), 'profile_record_type' => $questionType->value(), 'bot_id' => $botId->value(), 'ordinal_number' => $ordinalNumber, 'text' => $text],
            ]);
    }

    private function createRegistrationProgress(RegistrationQuestionId $registrationQuestionId, TelegramUserId $userId, OpenConnection $connection)
    {
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $registrationQuestionId->value()->pure()->raw(), 'user_id' => $userId->value()]
            ]);
    }

    private function firstRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('203729d6-330c-4123-b856-d5196812d509');
    }

    private function secondRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('303729d6-330c-4123-b856-d5196812d509');
    }

    private function futureMeetingRoundId(): string
    {
        return '72e7144a-e856-49b8-ad5e-30ce5fe0de00';
    }

    private function availablePositionIds()
    {
        return [(new ProductManager())->value(), (new ProductDesigner())->value()];
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function userReply(string $text, HttpTransport $transport, OpenConnection $connection)
    {
        return
            new SendsArbitraryMessage(
                new Now(),
                (new UserMessage($this->telegramUserId(), $text))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
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

    private function assertUserIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserStatus $userStatus, OpenConnection $connection)
    {
        $this->assertTrue(
            (new UserStatusFromBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureUserStatusFromPure($userStatus)
                )
        );
    }

    private function interestIds()
    {
        return [(new Networking())->value(), (new SpecificArea())->value()];
    }


    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function createBot(BotId $botId, array $availablePositionIds, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot', 'available_positions' => $availablePositionIds]
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
                [
                    'id' => $meetingRoundId,
                    'bot_id' => $botId->value(),
                    'start_date' => $startDateTime->value(),
                    'invitation_date' => $invitationDateTime->value(),
                    'available_interests' => $this->interestIds(),
                ]
            ]);
    }
}
