<?php

declare(strict_types=1);

namespace TG\Tests\Unit\UserActions\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use TG\Domain\Gender\Pure\Male as MaleGender;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\BotUser\UserId\BotUserId;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Infrastructure\Http\Request\Url\Basename\FromUrl as BasenameFromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\Method\GetUserProfilePhotos;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class RegisteredUserSendsArbitraryMessageTest extends TestCase
{
    public function testWhenRegisteredUserSendsArbitraryMessageThenHeSeesNextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRegisteredGayMale($this->userId(), $this->telegramUserId(), $connection);
        $this->createRegisteredGayMale($this->secondUserId(), $this->pairTelegramUserId(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply('эм..', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            (new GetUserProfilePhotos())->value(),
            (new BasenameFromUrl($transport->sentRequests()[0]->url()))->value()
        );
        $this->assertEquals(
            (new SendMediaGroup())->value(),
            (new BasenameFromUrl($transport->sentRequests()[1]->url()))->value()
        );
        $this->assertEquals(
            (new SendMessage())->value(),
            (new BasenameFromUrl($transport->sentRequests()[2]->url()))->value()
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

    private function secondUserId(): BotUserId
    {
        return new UserIdFromUuid(new FromString('222729d6-330c-4123-b856-d5196812d222'));
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
}