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
use TG\Domain\RegistrationAnswerOption\Single\Pure\AreYouReadyToRegister\RegisterInVisibleMode;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatDoYouPrefer\Men;
use TG\Domain\RegistrationAnswerOption\Single\Pure\WhatIsYourGender\Male;
use TG\Domain\UserMode\Impure\FromBotUser;
use TG\Domain\UserMode\Impure\FromPure as ImpureUserMode;
use TG\Domain\UserMode\Pure\Invisible;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\Http\Request\Url\Basename\FromUrl as BasenameFromUrl;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Tests\Infrastructure\Http\Response\Inbound\EmptyGetUserProfilePhotosResponse;
use TG\Tests\Infrastructure\Http\Response\Inbound\EmptySuccessfulResponse;
use TG\Tests\Infrastructure\Http\Response\Inbound\GetUserProfilePhotosResponse;
use TG\Tests\Infrastructure\Http\Response\Inbound\SuccessfulGetFileResponse;
use TG\Tests\Infrastructure\Http\Transport\ConfiguredByTelegramUserIdAndTelegramMethod;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNoAvatars;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\Method\GetFile;
use TG\Infrastructure\TelegramBot\Method\GetUserProfilePhotos;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
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
        $this->createNonRegisteredBotUser($this->userId(), $this->telegramUserId(), $connection);
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
        $this->createNonRegisteredBotUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createRegisteredGayMale($this->pairUserId(), $this->pairTelegramUserId(), $connection);
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

        $this->assertCount(1 + 1/* getProfilePictures */ + 1/* sendMedia */ + 1/* Поехали? */, $transport->sentRequests());
        $this->assertUserHasGender($this->userId(), new MaleGender(), $connection);
        $this->assertEquals(
            <<<t
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её будут видеть другие пользователи.

Если вас что-то беспокоит или у вас возникла проблема, в @flurr_support_bot вы можете задать любые вопросы.
t
            ,
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['media'], true)[0]['caption']
        );
        $this->assertEquals(
            [['text' => (new RegisterInVisibleMode())->value()]],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $this->userReply((new RegisterInVisibleMode())->value(), $transport, $connection)->response();

        $this->assertUserIsRegistered($this->userId(), $connection);
        $this->assertUserHasAvatars($this->userId(), $connection);
        $this->assertUserIsInVisibleMode($this->userId(), $connection);
        $this->assertCount(
            4
            + 1/* getProfilePictures for currently registered user */
            + 1/* congratulations and here is your first pair */
            + 1/* getProfilePictures for a pair */
            + 1/* sendMedia to current user */
            + 1/* sendMessage with name and reaction buttons to current user */,
            $transport->sentRequests()
        );
        $this->assertEquals(
            (new GetUserProfilePhotos())->value(),
            (new BasenameFromUrl($transport->sentRequests()[4]->url()))->value()
        );
        $this->assertEquals(
            (new SendMessage())->value(),
            (new BasenameFromUrl($transport->sentRequests()[5]->url()))->value()
        );
        $this->assertEquals(
            'Поздравляем, вы зарегистрировались! Вот ваша первая пара:',
            (new FromQuery(new FromUrl($transport->sentRequests()[5]->url())))->value()['text']
        );
        $this->assertEquals(
            (new GetUserProfilePhotos())->value(),
            (new BasenameFromUrl($transport->sentRequests()[6]->url()))->value()
        );
        $this->assertEquals(
            (new SendMediaGroup())->value(),
            (new BasenameFromUrl($transport->sentRequests()[7]->url()))->value()
        );
        $this->assertEquals(
            (new SendMessage())->value(),
            (new BasenameFromUrl($transport->sentRequests()[8]->url()))->value()
        );
    }

    public function testNewUserWithoutAvatarsRegisters()
    {
        $connection = new ApplicationConnection();
        $this->createNonRegisteredBotUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createRegisteredGayMale($this->pairUserId(), $this->pairTelegramUserId(), $connection);
        $transport = new TransportWithNoAvatars();

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
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её начнут видеть другие пользователи.

У вас в профиле telegram пока нет ни одного фото, и пока вы не загрузите хотя бы одно, другие пользователи вас не увидят. Вы можете пока зарегистрироваться и смотреть другие профили, а как будете готовы — просто загрузите аватарку в telegram.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.

Ну что, поехали?
t
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => (new RegisterInVisibleMode())->value()]],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );

        $transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair = $this->transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair();
        $this->userReply((new RegisterInVisibleMode())->value(), $transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair, $connection)->response();

        $this->assertUserIsRegistered($this->userId(), $connection);
        $this->assertUserHasNoAvatars($this->userId(), $connection);
        $this->assertUserIsInVisibleMode($this->userId(), $connection);
        $this->assertCount(
            1/* getProfilePictures for currently registered user */
            + 1/* congratulations and here is your first pair */
            + 1/* getProfilePictures for a pair */
            + 1/* sendMedia to current user */
            + 1/* sendMessage with name and reaction buttons to current user */,
            $transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()
        );
        $this->assertEquals(
            (new GetUserProfilePhotos())->value(),
            (new BasenameFromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[0]->url()))->value()
        );
        $this->assertEquals(
            (new SendMessage())->value(),
            (new BasenameFromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[1]->url()))->value()
        );
        $this->assertEquals(
            'Поздравляем, вы зарегистрировались! Вот ваша первая пара:',
            (new FromQuery(new FromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertEquals(
            (new GetUserProfilePhotos())->value(),
            (new BasenameFromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[2]->url()))->value()
        );
        $this->assertEquals(
            (new SendMediaGroup())->value(),
            (new BasenameFromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[3]->url()))->value()
        );
        $this->assertEquals(
            (new SendMessage())->value(),
            (new BasenameFromUrl($transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair->sentRequests()[4]->url()))->value()
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

    private function pairTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(12345);
    }

    private function userId(): BotUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function pairUserId(): BotUserId
    {
        return new UserIdFromUuid(new FromString('222729d6-330c-4123-b856-d5196812d222'));
    }

    private function createNonRegisteredBotUser(BotUserId $userId, InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => $userId->value(),
                    'first_name' => 'Vadim',
                    'last_name' => 'Samokhin',
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => 'dremuchee_bydlo',
                    'status' => (new RegistrationIsInProgress())->value(),
                ]
            ]);
    }

    private function createRegisteredGayMale(BotUserId $userId, InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => $userId->value(),
                    'first_name' => 'Vassil',
                    'last_name' => 'Krasavchicke',
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => 'vasily_sweet_boi',
                    'status' => (new Registered())->value(),
                    'gender' => (new MaleGender())->value(),
                    'preferred_gender' => (new MaleGender())->value(),
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

    private function assertUserHasNoAvatars(BotUserId $userId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ById($userId, $connection))->value()->pure()->raw()['has_avatar']
        );
    }

    private function assertUserHasAvatars(BotUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new ById($userId, $connection))->value()->pure()->raw()['has_avatar']
        );
    }

    private function assertUserIsInInvisibleMode(BotUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(new ById($userId, $connection)))
                ->equals(
                    new ImpureUserMode(new Invisible())
                )
        );
    }

    private function assertUserIsInVisibleMode(BotUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(new ById($userId, $connection)))
                ->equals(
                    new ImpureUserMode(new Visible())
                )
        );
    }

    private function transportWithNoAvatarsForUserJustRegisteredAndTwoAvatarsForPair()
    {
        return
            new ConfiguredByTelegramUserIdAndTelegramMethod([
                $this->telegramUserId()->value() => [
                    (new GetUserProfilePhotos())->value() => new EmptyGetUserProfilePhotosResponse(),
                    (new SendMediaGroup())->value() => new EmptySuccessfulResponse(),
                    (new SendMessage())->value() => new EmptySuccessfulResponse(),
                ],
                $this->pairTelegramUserId()->value() => [
                    (new GetUserProfilePhotos())->value() => new GetUserProfilePhotosResponse(2),
                    (new GetFile())->value() => new SuccessfulGetFileResponse(),
                ]
            ]);
    }
}