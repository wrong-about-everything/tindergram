<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\ByTelegramUserId;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Domain\TelegramUser\ByTelegramId;
use TG\Domain\TelegramUser\RegisteredInBot;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
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
use TG\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;
use TG\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessage;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use TG\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessageWithEmptyUsername;
use TG\UserActions\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserDoesNotHaveUsernameThenHeSeesAPromptMessageToSetIt()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessageWithEmptyUsername($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserDoesNotExist($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            <<<t
Не хотелось бы начинать знакомство с минорной ноты, но у меня нет другого выбора. Для того, чтобы мы смогли передать ваши контакты будущим собеседникам, нам нужно знать ваш ник, а он у вас не указан. Если не знаете, где именно всё это надо указать, вот пошаговая инструкция: https://aboutmessengers.ru/kak-pomenyat-imya-v-telegramme/. 

Если не знаете, какой ник выбрать, попробуйте просто набор цифр. Например, такой — {$this->time()}. Обещать не могу, но, думаю, он свободен.

Как будет готово, снова нажмите /start. 
t
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenNoUsersAreRegisteredInBotWhenNewUserPressesStartThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
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

    public function testGivenSomeUsersAreRegisteredInBotWhenNewUserPressesStartThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => 987654321, 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new Registered())->value()]
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
                ['registration_question_id' => $this->secondRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
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

    public function testWhenExistingButNotRegisteredUserPressesStartOneMoreTimeThenHeStillSeesTheFirstQuestion()
    {
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
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
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

    public function testWhenRegisteredUserPressesStartThenHeSeesWhatHeCanDo()
    {
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
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new Registered())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
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
            'Хотите что-то уточнить? Смело пишите на @tindergram_support_bot!',
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

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function firstRegistrationQuestionId()
    {
        return 'a2737a5d-9f02-4a62-886d-6f29cfbbccef';
    }

    private function secondRegistrationQuestionId()
    {
        return 'ddd7969c-02a3-447e-ab34-42cbea41a5d3';
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function assertUserExists(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $user = (new RegisteredInBot($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($user->pure()->isPresent());
        $this->assertEquals('Vadim', $user->pure()->raw()['first_name']);
        $this->assertEquals('Samokhin', $user->pure()->raw()['last_name']);
        $this->assertEquals('dremuchee_bydlo', $user->pure()->raw()['telegram_handle']);
        $profile = (new ByTelegramUserId($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($profile->pure()->isPresent());
    }

    private function assertUserDoesNotExist(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByTelegramId($telegramUserId, $connection))->value()->pure()->isPresent()
        );
    }

    private function time()
    {
        return time();
    }
}