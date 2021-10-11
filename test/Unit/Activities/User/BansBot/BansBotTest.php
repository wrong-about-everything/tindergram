<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\BansBot;

use PHPUnit\Framework\TestCase;
use TG\Activities\User\BansBot\BansBot;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\BotUser\UserStatus\Impure\FromBotUser;
use TG\Domain\BotUser\UserStatus\Impure\FromPure;
use TG\Domain\BotUser\UserStatus\Pure\InactiveAfterRegistered;
use TG\Domain\BotUser\UserStatus\Pure\InactiveBeforeRegistered;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\BotUser\UserStatus\Pure\UserStatus;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;

class BansBotTest extends TestCase
{
    public function testWhenNotYetRegisteredUserBansBotThenHeBecomesInactive()
    {
        $connection = new ApplicationConnection();
        $this->seedBotUser($this->telegramUserId(), new RegistrationIsInProgress(), $connection);

        $response = (new BansBot($this->telegramUserId(), $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertBotUserIsInactive($this->telegramUserId(), new InactiveBeforeRegistered(), $connection);
    }

    public function testWhenRegisteredUserBansBotThenHeBecomesInactive()
    {
        $connection = new ApplicationConnection();
        $this->seedBotUser($this->telegramUserId(), new Registered(), $connection);

        $response = (new BansBot($this->telegramUserId(), $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertBotUserIsInactive($this->telegramUserId(), new InactiveAfterRegistered(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seedBotUser(InternalTelegramUserId $internalTelegramUserId, UserStatus $status, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['telegram_id' => $internalTelegramUserId->value(), 'status' => $status->value()]
            ]);
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(1234);
    }

    private function assertBotUserIsInactive(InternalTelegramUserId $internalTelegramUserId, UserStatus $status, OpenConnection $connection)
    {
        $botUser = new ByInternalTelegramUserId($internalTelegramUserId, $connection);
        $this->assertTrue(
            (new FromBotUser($botUser))
                ->equals(
                    new FromPure($status)
                )
        );
    }
}