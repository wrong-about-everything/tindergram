<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use TG\Domain\Gender\Impure\BotUserPreferredGender;
use TG\Domain\BotUser\ReadModel\ById;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\Gender\Impure\FromBotUser as BotUserGender;
use TG\Domain\Gender\Impure\FromPure as ImpureGender;
use TG\Domain\Gender\Pure\Gender;
use TG\Domain\Gender\Pure\Male as MaleGender;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\BotUser\UserId\BotUserId;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser as UserStatusFromBotUser;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\Register;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Http\Transport\TransportForUserRegistrationWithoutAvatars;
use TG\Infrastructure\Http\Transport\TransportWithNAvatars;
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
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @flurr_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужские'], ['text' => 'Женские']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );
    }

    public function testNewUserWithTwoAvatarsRegisters()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->userId(), $this->telegramUserId(), new RegistrationIsInProgress(), $connection);
        $transport = new TransportWithNAvatars(2);

        $this->userReply((new Men())->value(), $transport, $connection)->response();

        $this->assertCount(1, $transport->sentRequests());
        $this->assertUserPreferredGender($this->userId(), new MaleGender(), $connection);
        $this->assertEquals(
            'Укажите свой пол',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужской'], ['text' => 'Женский']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $this->userReply((new Male())->value(), $transport, $connection)->response();

        $this->assertUserHasGender($this->userId(), new MaleGender(), $connection);
        $this->assertEquals(
            <<<t
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её сразу же начнут видеть другие пользователи.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.
t
            ,
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['media'], true)[0]['caption']
        );
        $this->assertEquals(
            [['text' => (new Register())->value()]],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[5]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $this->userReply((new Register())->value(), $transport, $connection)->response();

        $this->assertUserIsRegistered($this->userId(), $connection);
        $this->assertCount(7, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Через пару дней начнём присылать профили. Если хотите что-то спросить или уточнить, смело пишите на @flurr_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[6]->url())))->value()['text']
        );
    }

    public function testNewUserWithoutAvatarsRegisters()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->userId(), $this->telegramUserId(), new RegistrationIsInProgress(), $connection);
        $transport = new TransportForUserRegistrationWithoutAvatars();

        $this->userReply((new Men())->value(), $transport, $connection)->response();

        $this->assertCount(1, $transport->sentRequests());
        $this->assertUserPreferredGender($this->userId(), new MaleGender(), $connection);
        $this->assertEquals(
            'Укажите свой пол',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужской'], ['text' => 'Женский']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $this->userReply((new Male())->value(), $transport, $connection)->response();

        $this->assertUserHasGender($this->userId(), new MaleGender(), $connection);
        $this->assertEquals(
            <<<t
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её сразу же начнут видеть другие пользователи.

У вас в профиле telegram пока нет ни одного фото. Можете пока зарегистрироваться, а как будете готовы -- просто загрузите аватарку в telegram.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.
t
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => (new Register())->value()]],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $this->userReply((new Register())->value(), $transport, $connection)->response();

        $this->assertUserIsRegistered($this->userId(), $connection);
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Через пару дней начнём присылать профили. Если хотите что-то спросить или уточнить, смело пишите на @flurr_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
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
            'Хотите что-то уточнить? Смело пишите на @flurr_support_bot!',
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

    private function assertUserPreferredGender(BotUserId $userId, Gender $gender, OpenConnection $connection)
    {
        $this->assertEquals(
            $gender->value(),
            (new BotUserPreferredGender(new ById($userId, $connection)))->value()->pure()->raw()
        );
        $this->assertTrue(
            (new UserStatusFromBotUser(new ById($userId, $connection)))
                ->equals(
                    new FromPure(new RegistrationIsInProgress())
                )
        );
    }

    private function assertUserHasGender(BotUserId $userId, Gender $gender, OpenConnection $connection)
    {
        $this->assertTrue(
            (new BotUserGender(new ById($userId, $connection)))
                ->equals(
                    new ImpureGender($gender)
                )
        );
        $this->assertTrue(
            (new UserStatusFromBotUser(new ById($userId, $connection)))
                ->equals(
                    new FromPure(new RegistrationIsInProgress())
                )
        );
    }

    private function assertUserIsRegistered(BotUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new UserStatusFromBotUser(new ById($userId, $connection)))
                ->equals(
                    new FromPure(new Registered())
                )
        );
    }
}