<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\OneDay;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use TG\Activities\Cron\ChecksUserAvatar\ChecksUserAvatar;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\Logging\Logs\StdOut;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\Method\GetUserProfilePhotos;
use TG\Infrastructure\Uuid\RandomUUID;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Http\Response\Inbound\EmptyGetUserProfilePhotosResponse;
use TG\Tests\Infrastructure\Http\Response\Inbound\GetUserProfilePhotosResponse;
use TG\Tests\Infrastructure\Http\Transport\ConfiguredByTelegramUserIdAndTelegramMethod;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\BotUserAvatarCheck;

class ChecksUserAvatarTest extends TestCase
{
    public function testGivenThereAreNoCheckedUsersAtAllWhenThereAreNonCheckedUsersTodayThenCheckThem()
    {
        $connection = new ApplicationConnection();
        $this->createBotUserWithAvatar($this->firstTelegramUserId(), $connection);
        $this->createBotUserWithoutAvatar($this->secondTelegramUserId(), $connection);

        $transport =
            new ConfiguredByTelegramUserIdAndTelegramMethod([
                $this->firstTelegramUserId()->value() => [(new GetUserProfilePhotos())->value() => new EmptyGetUserProfilePhotosResponse()],
                $this->secondTelegramUserId()->value() => [(new GetUserProfilePhotos())->value() => new GetUserProfilePhotosResponse(9)],
            ]);

        $response = (new ChecksUserAvatar(new Now(), $transport, $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertUserHasAvatar($this->secondTelegramUserId(), $connection);
        $this->assertUserDoesNotHaveAvatar($this->firstTelegramUserId(), $connection);

        $secondTimeResponse = (new ChecksUserAvatar(new Now(), $transport, $connection, new DevNull()))->response();

        $this->assertTrue($secondTimeResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertUserHasAvatar($this->secondTelegramUserId(), $connection);
        $this->assertUserDoesNotHaveAvatar($this->firstTelegramUserId(), $connection);
    }

    public function testGivenThereAreUsersCheckedYesterdayWhenThereAreNonCheckedUsersTodayThenCheckThem()
    {
        $connection = new ApplicationConnection();
        $this->createBotUserAvatarCheck($this->firstTelegramUserId(), new Past(new Now(), new OneDay()), $connection);
        $this->createBotUserAvatarCheck($this->secondTelegramUserId(), new Past(new Now(), new OneDay()), $connection);
        $this->createBotUserWithAvatar($this->firstTelegramUserId(), $connection);
        $this->createBotUserWithoutAvatar($this->secondTelegramUserId(), $connection);

        $transport =
            new ConfiguredByTelegramUserIdAndTelegramMethod([
                $this->firstTelegramUserId()->value() => [(new GetUserProfilePhotos())->value() => new EmptyGetUserProfilePhotosResponse()],
                $this->secondTelegramUserId()->value() => [(new GetUserProfilePhotos())->value() => new GetUserProfilePhotosResponse(9)],
            ]);

        $response = (new ChecksUserAvatar(new Now(), $transport, $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertUserHasAvatar($this->secondTelegramUserId(), $connection);
        $this->assertUserDoesNotHaveAvatar($this->firstTelegramUserId(), $connection);

        $secondTimeResponse = (new ChecksUserAvatar(new Now(), $transport, $connection, new DevNull()))->response();

        $this->assertTrue($secondTimeResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertUserHasAvatar($this->secondTelegramUserId(), $connection);
        $this->assertUserDoesNotHaveAvatar($this->firstTelegramUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function createBotUserAvatarCheck(InternalTelegramUserId $telegramUserId, ISO8601DateTime $checkDateTime, OpenConnection $connection)
    {
        (new BotUserAvatarCheck($connection))
            ->insert([
                ['telegram_id' => $telegramUserId->value(), 'date' => $checkDateTime->value()]
            ]);
    }

    private function createBotUserWithAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['telegram_id' => $telegramUserId->value(), 'status' => (new Registered())->value(), 'has_avatar' => 1]
            ]);
    }

    private function createBotUserWithoutAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['telegram_id' => $telegramUserId->value(), 'status' => (new Registered())->value(), 'has_avatar' => 0]
            ]);
    }

    private function firstTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(1);
    }

    private function secondTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(2);
    }

    private function assertUserHasAvatar(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new ByInternalTelegramUserId($internalTelegramUserId, $connection))
                ->value()->pure()->raw()['has_avatar']
        );
    }

    private function assertUserDoesNotHaveAvatar(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByInternalTelegramUserId($internalTelegramUserId, $connection))
                ->value()->pure()->raw()['has_avatar']
        );
    }
}