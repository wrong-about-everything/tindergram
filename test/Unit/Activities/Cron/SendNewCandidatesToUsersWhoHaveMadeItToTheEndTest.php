<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\Cron\SendNewCandidatesToUsers\SendNewCandidatesToUsersWhoHaveMadeItToTheEnd;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\Reaction\Pure\Dislike;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\NonExistent;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsDown as ThumbsDownButton;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsUp as ThumbsUpButton;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\UserStory\UserStory;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\ViewedPair;

class SendNewCandidatesToUsersWhoHaveMadeItToTheEndTest extends TestCase
{
    public function testWhenSomeUsersHaveMadeItToTheEndThenSendThemNewCandidates()
    {
        $connection = new ApplicationConnection();

        $now = new FromISO8601('2020-10-11T20:47:45-05');

        $this->createMaleUserPreferringFemales($this->firstRecipientTelegramId(), 'Vasya', $connection);
        $this->createMaleUserPreferringFemales($this->secondRecipientTelegramId(), 'Vasya 2', $connection);
        $this->createMaleUserPreferringFemales($this->thirdRecipientTelegramId(), 'Vasya 3', $connection);
        $this->createInactiveMaleUserPreferringFemales($this->fourthRecipientTelegramId(), 'Vasya 4', $connection);

        $this->createFemaleUserPreferringMales($this->firstPairTelegramId(), 'Fedya', $connection);
        $this->createFemaleUserPreferringMales($this->secondPairTelegramId(), 'Anatoly', $connection);

        $this->seedPair($this->firstRecipientTelegramId(), $this->firstPairTelegramId(), new Past($now, new NDays(3)), new Like(), $connection);
        $this->seedPair($this->secondRecipientTelegramId(), $this->firstPairTelegramId(), new Past($now, new NDays(3)), new NonExistent(), $connection);
        $this->seedPair($this->thirdRecipientTelegramId(), $this->firstPairTelegramId(), new FromISO8601('2020-10-12T04:39:11+14')/*2020-10-11T09:39:11-05*/, new Dislike(), $connection);
        $this->seedPair($this->fourthRecipientTelegramId(), $this->firstPairTelegramId(), new Past($now, new NDays(3)), new Like(), $connection);

        $transport = new TransportWithNAvatars(2);
        $response = $this->userStory($now, $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertEquals(
            [(new ThumbsDownButton($this->secondPairTelegramId()))->value(), (new ThumbsUpButton($this->secondPairTelegramId()))->value()],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['reply_markup'], true)['inline_keyboard'][0]
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function firstRecipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(1111111);
    }

    private function secondRecipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(444444);
    }

    private function thirdRecipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(5555555);
    }

    private function fourthRecipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(6666666);
    }

    private function firstPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(22222222);
    }

    private function secondPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(3333333333333);
    }

    private function createMaleUserPreferringFemales(InternalTelegramUserId $telegramUserId, string $name, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => $name,
                    'telegram_id' => $telegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => (new Male())->value(),
                    'preferred_gender' => (new Female())->value(),
                    'user_mode' => (new Visible())->value(),
                    'account_paused' => 0,

                    'has_avatar' => 1,
                ]
            ]);
    }

    private function createInactiveMaleUserPreferringFemales(InternalTelegramUserId $telegramUserId, string $name, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => $name,
                    'telegram_id' => $telegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => (new Male())->value(),
                    'preferred_gender' => (new Female())->value(),
                    'user_mode' => (new Visible())->value(),
                    'account_paused' => 1,

                    'has_avatar' => 1,
                ]
            ]);
    }

    private function createFemaleUserPreferringMales(InternalTelegramUserId $telegramUserId, string $name, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => $name,
                    'telegram_id' => $telegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => (new Female())->value(),
                    'preferred_gender' => (new Male())->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 1,
                ]
            ]);
    }

    private function seedPair(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, ISO8601DateTime $viewedAt, Reaction $reaction, OpenConnection $connection)
    {
        (new ViewedPair($connection))
            ->insert([
                [
                    'recipient_telegram_id' => $recipientTelegramId->value(),
                    'pair_telegram_id' => $pairTelegramId->value(),
                    'viewed_at' => $viewedAt->value(),
                    'reaction' => $reaction->exists() ? $reaction->value() : null,
                ]
            ]);
    }

    private function userStory(ISO8601DateTime $now, HttpTransport $transport, OpenConnection $connection): UserStory
    {
        return new SendNewCandidatesToUsersWhoHaveMadeItToTheEnd($now, $transport, $connection, new DevNull());
    }
}