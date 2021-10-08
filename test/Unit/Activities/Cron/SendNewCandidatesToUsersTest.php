<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\Cron;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\Cron\SendNewCandidatesToUsers\SendNewCandidatesToUsers;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Gender;
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
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\UserStory\UserStory;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\ViewedPair;

class SendNewCandidatesToUsersTest extends TestCase
{
    public function testWhenSomeUsersHaveMadeItToTheEndThenSendThemNewCandidates()
    {
        $connection = new ApplicationConnection();

        $now = new FromISO8601('2020-10-11T20:47:45-05');

        $this->createBotUser($this->firstRecipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), 0, $connection);
        $this->createBotUser($this->secondRecipientTelegramId(), 'Vasya 2', 'vasya 2', new Male(), new Female(), 0, $connection);
        $this->createBotUser($this->thirdRecipientTelegramId(), 'Vasya 2', 'vasya 2', new Male(), new Female(), 0, $connection);

        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->createBotUser($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 0, $connection);

        $this->seedPair($this->firstRecipientTelegramId(), $this->firstPairTelegramId(), new Past($now, new NDays(3)), new Like(), $connection);
        $this->seedPair($this->secondRecipientTelegramId(), $this->firstPairTelegramId(), new Past($now, new NDays(3)), new NonExistent(), $connection);
        $this->seedPair($this->thirdRecipientTelegramId(), $this->firstPairTelegramId(), new FromISO8601('2020-10-12T04:39:11+14')/*2020-10-11T09:39:11-05*/, new Dislike(), $connection);

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

    private function firstPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(22222222);
    }

    private function secondPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(3333333333333);
    }

    private function createBotUser(
        InternalTelegramUserId $telegramUserId,
        string $name,
        string $handle,
        Gender $gender,
        Gender $preferredGender,
        int $seenQty,
        OpenConnection $connection
    )
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => $name,
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => $handle,
                    'status' => (new Registered())->value(),

                    'gender' => $gender->value(),
                    'preferred_gender' => $preferredGender->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 1,
                    'seen_qty' => $seenQty
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
        return new SendNewCandidatesToUsers($now, $transport, $connection, new DevNull());
    }
}