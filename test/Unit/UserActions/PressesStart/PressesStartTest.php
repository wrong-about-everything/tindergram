<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\BotUser\UserId\BotUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessage;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessageWithEmptyUsername;
use TG\UserActions\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserDoesNotHaveUsernameThenHeSeesAPromptMessageToSetIt()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessageWithEmptyUsername($this->telegramUserId()))->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserDoesNotExist($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Hey sweetie, у тебя не установлен ник! Как установишь, снова жми /start.',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenNoUsersAreRegisteredInBotAndThereAreNoRegistrationQuestionsWhenNewUserPressesStartThenHeSeesCongratulation()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @hey_sweetie_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserPressesStartOneMoreTimeThenHeStillSeesTheFirstQuestion()
    {
        $this->markTestIncomplete();

        $connection = new ApplicationConnection();
        (new BotUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo', 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Какая у вас должность?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserWhoHasAnsweredOneQuestionPressesStartThenHeSeesTheSecondQuestion()
    {
        $this->markTestIncomplete();

        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserWhoHasAnsweredAllButOneQuestionPressesStartThenHeSeesTheLastQuestion()
    {
        $this->markTestIncomplete();

        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А где работаете?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 3, 'text' => 'А опыт?'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
                ['registration_question_id' => $this->secondRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserPressesStartThenHeSeesWhatCanIDoForYouMessage()
    {
        $connection = new ApplicationConnection();
        (new BotUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo', 'status' => (new Registered())->value()]
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @hey_sweetie_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function firstRegistrationQuestionId()
    {
        return 'a2737a5d-9f02-4a62-886d-6f29cfbbccef';
    }

    private function secondRegistrationQuestionId()
    {
        return 'ddd7969c-02a3-447e-ab34-42cbea41a5d3';
    }

    private function userId(): BotUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function assertUserExists(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $user = (new ByInternalTelegramUserId($telegramUserId, $connection))->value();
        $this->assertTrue($user->pure()->isPresent());
        $this->assertEquals('Vadim', $user->pure()->raw()['first_name']);
        $this->assertEquals('Samokhin', $user->pure()->raw()['last_name']);
        $this->assertEquals('dremuchee_bydlo', $user->pure()->raw()['telegram_handle']);
    }

    private function assertUserDoesNotExist(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByInternalTelegramUserId($telegramUserId, $connection))->value()->pure()->isPresent()
        );
    }
}