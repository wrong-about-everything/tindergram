<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\BotUser\Preferences\Pure\Men;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\BotUser\UserId\BotUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\UserStory\UserStory;
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

    public function testWhenNewUserPressesStartSeveralTimesThenHeStillSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();

        $response = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Какие аккаунты вам показывать?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $response = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Какие аккаунты вам показывать?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenUserHasSetPreferencesWhenHePressesStartThenHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();

        $this->seedNotRegisteredBotUser($this->telegramUserId(), $connection);

        $response = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Укажите свой пол',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $response = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Укажите свой пол',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserPressesStartThenHeStillSeesHowCanIHelpYouMessage()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();
        $this->seedRegisteredBotUser($this->telegramUserId(), $connection);

        $response = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @hey_sweetie_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $response2 = $this->pressesStart($this->telegramUserId(), $transport, $connection)->response();

        $this->assertTrue($response2->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @hey_sweetie_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
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

    private function pressesStart(InternalTelegramUserId $telegramUserId, HttpTransport $transport, OpenConnection $connection): UserStory
    {
        return
            new PressesStart(
                (new StartCommandMessage($telegramUserId))->value(),
                $transport,
                $connection,
                new DevNull()
            );
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

    private function seedNotRegisteredBotUser(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_handle' => 'dremuchee_bydlo', 'telegram_id' => $telegramUserId->value(), 'status' => (new RegistrationIsInProgress())->value(), 'preferences' => (new Men())->value()]
            ]);
    }

    private function seedRegisteredBotUser(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_handle' => 'dremuchee_bydlo', 'telegram_id' => $telegramUserId->value(), 'status' => (new Registered())->value(), 'preferences' => (new Men())->value(), 'gender' => (new Male())->value()]
            ]);
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