<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron\KicksOffANewSpot;

use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use TG\Activities\Cron\KicksOffANewSpot\KicksOffANewSpot;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Gender;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Http\Transport\TransportWithTwoAvatars;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\Logging\Logs\StdOut;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\RandomUUID;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Http\Transport\FakeTransport;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\View;

class KickOffANewSpotTest extends TestCase
{
    public function test()
    {
        $transport = new TransportWithTwoAvatars();
        $connection = new ApplicationConnection();

        $this->seedUser($this->firstManPreferringWomenTelegramId(), new Male(), new Female(), $connection);
        $this->seedUser($this->secondManPreferringWomenTelegramId(), new Male(), new Female(), $connection);
        $this->seedUser($this->thirdManPreferringWomenTelegramId(), new Male(), new Female(), $connection);
        $this->seedUser($this->firstWomanPreferringMenTelegramId(), new Female(), new Male(), $connection);
        $this->seedUser($this->secondWomanPreferringMenTelegramId(), new Female(), new Male(), $connection);
        $this->seedUser($this->thirdWomanPreferringMenTelegramId(), new Female(), new Male(), $connection);

        $response = (new KicksOffANewSpot($transport, $connection, new DevNull()))->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPhotosAreSent(6, $transport);
        $this->assertAllUsersAreInitiated(6, $connection);
        $this->assertViewsCreated(6, $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seedUser(InternalTelegramUserId $internalTelegramUserId, Gender $gender, Gender $preferredGender, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $internalTelegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => $gender->value(),
                    'preferred_gender' => $preferredGender->value(),

                    'seen_qty' => 0
                ]
            ]);
    }

    private function firstManPreferringWomenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(1);
    }

    private function secondManPreferringWomenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(2);
    }

    private function thirdManPreferringWomenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(3);
    }

    private function firstWomanPreferringMenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(4);
    }

    private function secondWomanPreferringMenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(5);
    }

    private function thirdWomanPreferringMenTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(6);
    }

    private function assertPhotosAreSent(int $usersQty, FakeTransport $transport)
    {
        $this->assertCount(
            $usersQty * (1/*get user profile images request*/ + 2/*get file requests*/ + 1/*send media request*/ + 1/*send pair info with vote emojis*/),
            $transport->sentRequests()
        );
    }

    private function assertAllUsersAreInitiated(int $usersQty, OpenConnection $connection)
    {
        $this->assertCount(
            $usersQty,
            (new Selecting(
                'select * from bot_user where is_initiated = ?',
                [1],
                $connection
            ))
                ->response()->pure()->raw()
        );
        $this->assertCount(
            0,
            (new Selecting(
                'select * from bot_user where is_initiated = ?',
                [0],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertViewsCreated(int $viewsQty, OpenConnection $connection)
    {
        $this->assertCount(
            $viewsQty,
            (new Selecting(
                'select * from view',
                [],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }
}