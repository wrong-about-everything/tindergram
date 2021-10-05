<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\BansBot;

use PHPUnit\Framework\TestCase;
use TG\Activities\User\BansBot\BansBot;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
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
    public function test()
    {
        $connection = new ApplicationConnection();
        $this->seedBotUser($this->telegramUserId(), $connection);

        $response = (new BansBot($this->telegramUserId(), $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertBotUserIsInactive($this->telegramUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seedBotUser(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['telegram_id' => $internalTelegramUserId->value()]
            ]);
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(1234);
    }

    private function assertBotUserIsInactive(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        $botUser = new ByInternalTelegramUserId($internalTelegramUserId, $connection);
        $this->assertTrue($botUser->value()->pure()->raw()['account_paused']);
    }
}