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
use TG\Domain\About\Impure\FromBotUser as AboutBotUser;
use TG\Domain\BotUser\ByTelegramUserId;
use TG\Domain\Experience\ExperienceId\Impure\FromBotUser as ExperienceFromBotUser;
use TG\Domain\Experience\ExperienceId\Impure\FromPure as ImpureExperienceFromPure;
use TG\Domain\Experience\ExperienceId\Pure\Experience as UserExperience;
use TG\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use TG\Domain\Experience\ExperienceName\LessThanAYearName;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\Position\PositionId\Impure\FromBotUser;
use TG\Domain\Position\PositionId\Impure\FromPure;
use TG\Domain\Position\PositionId\Pure\Position as UserPosition;
use TG\Domain\Position\PositionId\Pure\ProductDesigner;
use TG\Domain\Position\PositionId\Pure\ProductManager;
use TG\Domain\Position\PositionName\ProductDesignerName;
use TG\Domain\Position\PositionName\ProductManagerName;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\FromString as RegistrationQuestionIdFromString;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\RegistrationQuestionId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\RegistrationQuestionType;
use TG\Domain\TelegramBot\UserMessage\Pure\Skipped;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser as UserStatusFromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
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

class UserRegistersInBotTest extends TestCase
{
    public function testWhenNewUserAnswersTheFirstQuestionThenHisAnswerIsPersistedAndHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new Experience(), $this->botId(), 2, 'А опыт?', $connection);
        $transport = new Indifferent();

        $response = $this->userReply((new ProductManagerName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionId(), $connection);
        $this->assertPositionIs($this->telegramUserId(), $this->botId(), new ProductManager(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenNewUserAnswersTheFirstQuestionWithTextInsteadOfPressingButtonThenHeSeesValidationErrorAndWhenHeAnswersWithAButtonThenHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new Experience(), $this->botId(), 2, 'А опыт?', $connection);
        $transport = new Indifferent();

        $response = $this->userReply('что?', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertReplyButtons($transport->sentRequests()[0]);

        $secondResponse = $this->userReply((new ProductManagerName())->value(), $transport, $connection)->response();

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionId(), $connection);
        $this->assertPositionIs($this->telegramUserId(), $this->botId(), new ProductManager(), $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
    }

    public function testWhenNewUserAnswersTheFirstQuestionWithTextInsteadOfPressingButtonThenHeSeesValidationError()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new Experience(), $this->botId(), 2, 'А опыт?', $connection);
        $transport = new Indifferent();

        $response = $this->userReply('что?', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserAnswersTheLastQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new Experience(), $this->botId(), 2, 'А опыт?', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionId(), $this->userId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionId(), $connection);
        $this->assertExperienceIs($this->telegramUserId(), $this->botId(), new LessThanAYear(), $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserSkipsTheLastAboutMeQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new About(), $this->botId(), 2, 'Расскажите плз о себе, если хотите', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionId(), $this->userId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply((new Skipped())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionId(), $connection);
        $this->assertAboutMeIsEmpty($this->telegramUserId(), $this->botId(), $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserAnswersTheLastAboutMeQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionId(), new Position(), $this->botId(), 1, 'Какая у вас должность?', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionId(), new About(), $this->botId(), 2, 'Расскажите плз о себе, если хотите', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionId(), $this->userId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply('охохооо, как же много я могу о себе рассказать!', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionId(), $connection);
        $this->assertAboutMeIs($this->telegramUserId(), $this->botId(), 'охохооо, как же много я могу о себе рассказать!', $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @tindergram_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserSendsArbitraryMessageThenHeSeesStatusInfo()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @tindergram_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenMeetingRoundAheadWhenUserAnswersTheLastQuestionThenHeSeesAnInvitationToAMeetingRound()
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

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionId(), $connection);
        $this->assertExperienceIs($this->telegramUserId(), $this->botId(), new LessThanAYear(), $connection);
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
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
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

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function firstRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('203729d6-330c-4123-b856-d5196812d509');
    }

    private function futureMeetingRoundId(): string
    {
        return Uuid::uuid4()->toString();
    }

    private function secondRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('303729d6-330c-4123-b856-d5196812d509');
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
                [
                    'bot_id' => $botId->value(),
                    'user_id' => $userId->value(),
                    'status' => $status->value(),
                    'position' => null,
                    'experience' => null,
                    'about' => null,
                ]
            ]);
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

    private function createMeetingRound(string $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, ISO8601DateTime $invitationDateTime, $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId, 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value(), 'invitation_date' => $invitationDateTime->value()]
            ]);
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

    private function assertUserRegistrationProgressUpdated(TelegramUserId $userId, RegistrationQuestionId $registrationQuestionId, OpenConnection $connection)
    {
        $this->assertNotEmpty(
            (new Selecting(
                <<<q
select *
from user_registration_progress urp
where urp.user_id = ? and urp.registration_question_id = ?
q
                ,
                [$userId->value(), $registrationQuestionId->value()->pure()->raw()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertPositionIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserPosition $position, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new FromPure($position)
                )
        );
    }

    private function assertExperienceIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserExperience $experience, OpenConnection $connection)
    {
        $this->assertTrue(
            (new ExperienceFromBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureExperienceFromPure($experience)
                )
        );
    }

    private function assertAboutMeIsEmpty(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new AboutBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->empty()->pure()->raw()
        );
    }

    private function assertAboutMeIs(InternalTelegramUserId $telegramUserId, BotId $botId, string $about, OpenConnection $connection)
    {
        $this->assertEquals(
            $about,
            (new AboutBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->value()->pure()->raw()
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

    private function assertReplyButtons(Request $request)
    {
        $this->assertEquals(
            [
                [['text' => (new ProductManagerName())->value()], ['text' => (new ProductDesignerName())->value()]]
            ],
            json_decode((new FromQuery(new FromUrl($request->url())))->value()['reply_markup'], true)['keyboard']
        );
    }
}