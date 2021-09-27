<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\RatesAPair;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\User\RatesAPair\RatesAPair;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Gender;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;
use TG\Domain\InternalApi\RateCallbackData\ThumbsDown;
use TG\Domain\InternalApi\RateCallbackData\ThumbsUp as ThumbsUpCallbackData;
use TG\Domain\Reaction\Pure\Dislike;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\NonExistent;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsUp;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsDown as ThumbsDownButton;
use TG\Domain\TelegramBot\MessageToUser\ThatsAllForNow;
use TG\Domain\TelegramBot\MessageToUser\YouCanNotRateAUserMoreThanOnce;
use TG\Domain\TelegramBot\MessageToUser\YouHaveAMatch;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Tests\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\ViewedPair;

class RatesAPairTest extends TestCase
{
    public function testGivenPairViewedCurrentUserButHavenRatedHimYetWhenUserDownvotesAPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new NonExistent(), $connection);
        $this->createBotUser($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsDown($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(5, $transport->sentRequests());
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertEquals(
            [(new ThumbsDownButton($this->secondPairTelegramId()))->value(), (new ThumbsUp($this->secondPairTelegramId()))->value()],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['reply_markup'], true)['inline_keyboard'][0]
        );
    }

    public function testGivenPairHasntViewedCurrentUserWhenUserUpvotesAPairAndItIsNotMutualThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUser($this->secondPairTelegramId(), 'Anatoly', 'trol', new Female(), new Male(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(5, $transport->sentRequests());
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertEquals(
            [(new ThumbsDownButton($this->secondPairTelegramId()))->value(), (new ThumbsUp($this->secondPairTelegramId()))->value()],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['reply_markup'], true)['inline_keyboard'][0]
        );
    }

    public function testWhenUserUpvotesAPairAndItIsMutualThenHisChoiceIsPersistedAndHeSeesCongratulationsAndANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Like(), $connection);
        $this->createBotUser($this->secondPairTelegramId(), 'Anatoly', 'anatoly', new Female(), new Male(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(7, $transport->sentRequests());
        $this->assertEquals(
            (new YouHaveAMatch('fedya'))->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            (new YouHaveAMatch('vasya'))->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[6]->url())))->value()['text']
        );
        $this->assertEquals(
            [(new ThumbsDownButton($this->secondPairTelegramId()))->value(), (new ThumbsUp($this->secondPairTelegramId()))->value()],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[6]->url())))->value()['reply_markup'], true)['inline_keyboard'][0]
        );
    }

    public function testGivenNoPairsLeftWhenUserRatesCurrentPairThenHeSeesThatsAllForNowMessage()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Dislike(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            (new ThatsAllForNow())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenUserRatesPairOneMoreTimeThenHeSeesThatItIsNotAllowedAndHeSeesNextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $this->createBotUser($this->secondPairTelegramId(), 'Anatoly', 'anatoly', new Female(), new Male(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(6, $transport->sentRequests());
        $this->assertEquals(
            (new YouCanNotRateAUserMoreThanOnce())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[5]->url())))->value()['text']
        );
    }

    public function testGivenNoMorePairsLeftWhenUserRatesPairOneMoreTimeThenHeSeesThatItIsNotAllowedAndHeSeesThatsAllForNowMessage()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), $connection);
        $this->createBotUser($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), $this->firstPairTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            (new YouCanNotRateAUserMoreThanOnce())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            (new ThatsAllForNow())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function recipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(1111111);
    }

    private function firstPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(22222222);
    }

    private function secondPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(3333333333333);
    }

    private function createBotUser(InternalTelegramUserId $telegramUserId, string $name, string $handle, Gender $gender, Gender $preferredGender, OpenConnection $connection)
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
                ]
            ]);
    }

    private function seedPair(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, Reaction $reaction, OpenConnection $connection)
    {
        (new ViewedPair($connection))
            ->insert([
                [
                    'recipient_telegram_id' => $recipientTelegramId->value(),
                    'pair_telegram_id' => $pairTelegramId->value(),
                    'reaction' => $reaction->exists() ? $reaction->value() : null
                ]
            ]);
    }

    private function userReply(InternalTelegramUserId $voterTelegramId, InternalTelegramUserId $pairTelegramId, RateCallbackData $callbackData, HttpTransport $transport, OpenConnection $connection)
    {
        return
            new RatesAPair(
                $voterTelegramId,
                $callbackData,
                $transport,
                $connection,
                new DevNull()
            );
    }
}