<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use TG\Domain\BotUser\Preference\Multiple\Impure\FromBotUser;
use TG\Domain\BotUser\Preference\Single\Pure\Men as MenPreferenceId;
use TG\Domain\BotUser\Preference\Single\Pure\PreferenceId;
use TG\Domain\BotUser\ReadModel\ById;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\BotUser\UserId\BotUserId;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser as UserStatusFromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserRegistersInBotTest extends TestCase
{
    public function testWhenNewUserAnswersTheFirstQuestionWithCustomTextInsteadOfPushingAButtonThenHeSeesValidationError()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->userId(), $this->telegramUserId(), new RegistrationIsInProgress(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply('What?', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @hey_sweetie_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужские'], ['text' => 'Женские']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );
    }

    public function testWhenNewUserAnswersTheFirstQuestionThenHisAnswerIsPersistedAndHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->userId(), $this->telegramUserId(), new RegistrationIsInProgress(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply((new Men())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertUserHasPreferences($this->userId(), new MenPreferenceId(), $connection);
        $this->assertEquals(
            'Укажите свой пол',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужской'], ['text' => 'Женский']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );
    }

    public function testWhenExistingButNotYetRegisteredUserAnswersTheLastQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $this->markTestIncomplete();

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
            'Поздравляю, вы зарегистрировались! Если хотите что-то спросить или уточнить, смело пишите на @hey_sweetie_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserSendsArbitraryMessageThenHeSeesStatusInfo()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->userId(), $this->telegramUserId(), new Registered(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply('эм..', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
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

    private function userId(): BotUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function createBotUser(BotUserId $userId, InternalTelegramUserId $telegramUserId, UserStatus $status, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => $userId->value(),
                    'first_name' => 'Vadim',
                    'last_name' => 'Samokhin',
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => 'dremuchee_bydlo',
                    'status' => $status->value(),
                ]
            ]);
    }

    private function userReply(string $text, HttpTransport $transport, OpenConnection $connection)
    {
        return
            new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), $text))->value(),
                $transport,
                $connection,
                new DevNull()
            );
    }

    private function assertUserIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserStatus $userStatus, OpenConnection $connection)
    {
        $this->assertTrue(
            (new UserStatusFromBotUser(
                new ByInternalTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureUserStatusFromPure($userStatus)
                )
        );
    }

    private function assertUserHasPreferences(BotUserId $userId, PreferenceId $preferenceId, OpenConnection $connection)
    {
        $this->assertEquals(
            [$preferenceId->value()],
            (new FromBotUser(new ById($userId, $connection)))->value()->pure()->raw()
        );
        $this->assertTrue(
            (new UserStatusFromBotUser(new ById($userId, $connection)))
                ->equals(
                    new FromPure(new RegistrationIsInProgress())
                )
        );
    }
}