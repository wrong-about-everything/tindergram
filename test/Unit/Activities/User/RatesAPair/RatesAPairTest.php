<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\RatesAPair;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\User\RatesAPair\RatesAPair;
use TG\Domain\ABTesting\Pure\SwitchToVisibleModeOnRequest;
use TG\Domain\ABTesting\Pure\SwitchToVisibleModeOnUpvote;
use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Gender;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;
use TG\Domain\InternalApi\RateCallbackData\ThumbsDown as ThumbsDownCallbackData;
use TG\Domain\InternalApi\RateCallbackData\ThumbsUp as ThumbsUpCallbackData;
use TG\Domain\Pair\ReadModel\ByVoterTelegramIdAndRatedTelegramId;
use TG\Domain\Reaction\Pure\Dislike;
use TG\Domain\Reaction\Pure\FromViewedPair;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\NonExistent;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsUp as ThumbsUpButton;
use TG\Domain\TelegramBot\InlineKeyboardButton\Single\ThumbsDown as ThumbsDownButton;
use TG\Domain\TelegramBot\MessageToUser\ThatsAllForNow;
use TG\Domain\TelegramBot\MessageToUser\YouCanNotRateAUserMoreThanOnce;
use TG\Domain\TelegramBot\MessageToUser\YouHaveAMatch;
use TG\Domain\UserMode\Impure\FromBotUser;
use TG\Domain\UserMode\Impure\FromPure;
use TG\Domain\UserMode\Pure\Invisible;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\ABTesting\Pure\VariantId;
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

// @todo: Add tests for reaction persisting, like_qty and dislike_qty
class RatesAPairTest extends TestCase
{
    public function testWhenUserInInvisibleModeTurningOffOnUpvoteUpvotesAPairThenHeSwitchesToVisibleModeAndSeesTheNextOneWithAvatarAndInVisibleMode()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithoutAvatar($this->thirdPairTelegramId(), new Female(), new Male(), 0, $connection);
        $this->createBotUserWithAvatarButInInvisibleMode($this->fourthPairTelegramId(), new Female(), new Male(), 5, $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 10, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
        $this->assertUserIsInVisibleMode($this->recipientTelegramId(), $connection);
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

    public function testWhenUserInInvisibleModeTurningOffOnSupportRequestUpvotesAPairThenHeDoesNotBecomeVisibleAndSeesTheNextOneWithAvatarAndInVisibleMode()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnRequest(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithoutAvatar($this->thirdPairTelegramId(), new Female(), new Male(), 0, $connection);
        $this->createBotUserWithAvatarButInInvisibleMode($this->fourthPairTelegramId(), new Female(), new Male(), 5, $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 10, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
        $this->assertUserIsInInvisibleMode($this->recipientTelegramId(), $connection);
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

    public function testWhenUserInInvisibleModeDownvotesAPairThenHeRemainsInInvisibleModeAndSeesTheNextOneWithAvatarAndInVisibleMode()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithoutAvatar($this->thirdPairTelegramId(), new Female(), new Male(), 0, $connection);
        $this->createBotUserWithAvatarButInInvisibleMode($this->fourthPairTelegramId(), new Female(), new Male(), 5, $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 10, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsDownCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $this->assertUserIsInInvisibleMode($this->recipientTelegramId(), $connection);
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

    public function testWhenUserRatesAPairThenHeSeesTheNextOneWithAvatarAndInVisibleMode()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithoutAvatar($this->thirdPairTelegramId(), new Female(), new Male(), 0, $connection);
        $this->createBotUserWithAvatarButInInvisibleMode($this->fourthPairTelegramId(), new Female(), new Male(), 5, $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 10, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsDownCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
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

    public function testGivenPairViewedCurrentUserButHaventRatedHimYetWhenUserDownvotesAPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsDownCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
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

    public function testGivenPairHasntViewedCurrentUserWhenUserUpvotesAPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'trol', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
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

    public function testGivenPairDownvotedCurrentUserWhenUserDownvotesAPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Dislike(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsDownCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
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

    public function testGivenPairDownvotedCurrentUserWhenUserUpvotesAPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Dislike(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatol', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
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

    public function testGivenPairUpvotedCurrentUserWhenUserUpvotesAPairThenHisChoiceIsPersistedAndHeSeesCongratulationsAndANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUserWithAvatarAndInVisibleMode($this->recipientTelegramId(), 'Vasya', 'vasya', new Male(), new Female(), 0, $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Like(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatoly', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
        $this->assertCount(5, $transport->sentRequests());
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
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertEquals(
            [(new ThumbsDownButton($this->secondPairTelegramId()))->value(), (new ThumbsUpButton($this->secondPairTelegramId()))->value()],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['reply_markup'], true)['inline_keyboard'][0]
        );
    }

    public function testGivenNoPairsLeftWhenUserRatesCurrentPairThenHeSeesThatsAllForNowMessage()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new NonExistent(), $connection);
        $this->seedPair($this->firstPairTelegramId(), $this->recipientTelegramId(), new Dislike(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Like(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            (new ThatsAllForNow())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenUserRatesPairOneMoreTimeThenHeSeesThatItIsNotAllowedAndHeSeesNextPair()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->secondPairTelegramId(), 'Anatoly', 'anatoly', new Female(), new Male(), 0, $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            (new YouCanNotRateAUserMoreThanOnce())->value(),
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            'Anatoly',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
    }

    public function testGivenNoMorePairsLeftWhenUserUpvotesPairOneMoreTimeThenHeSeesThatItIsNotAllowedAndHeSeesThatsAllForNowMessage()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsUpCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
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

    public function testGivenNoMorePairsLeftWhenUserDownvotesPairOneMoreTimeThenHeSeesThatItIsNotAllowedAndHeSeesThatsAllForNowMessage()
    {
        $connection = new ApplicationConnection();
        $this->createRecipientMaleInInvisibleModePreferringFemales($this->recipientTelegramId(), new SwitchToVisibleModeOnUpvote(), $connection);
        $this->createBotUserWithAvatarAndInVisibleMode($this->firstPairTelegramId(), 'Fedya', 'fedya', new Female(), new Male(), 0, $connection);
        $this->seedPair($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
        $transport = new TransportWithNAvatars(2);

        $response = $this->userReply($this->recipientTelegramId(), new ThumbsDownCallbackData($this->firstPairTelegramId()), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertPairPersisted($this->recipientTelegramId(), $this->firstPairTelegramId(), new Dislike(), $connection);
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

    private function thirdPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(44444444);
    }

    private function fourthPairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(55555);
    }

    private function createBotUserWithAvatarAndInVisibleMode(
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

    private function createBotUserWithAvatarAndInInvisibleMode(
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
                    'user_mode' => (new Invisible())->value(),

                    'has_avatar' => 1,
                    'seen_qty' => $seenQty
                ]
            ]);
    }

    private function createRecipientMaleInInvisibleModePreferringFemales(InternalTelegramUserId $telegramUserId, VariantId $variantId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'telegram_id' => $telegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => (new Male())->value(),
                    'preferred_gender' => (new Female())->value(),
                    'user_mode' => (new Invisible())->value(),
                    'variant_id' => $variantId->value(),

                    'has_avatar' => 1,
                ]
            ]);
    }

    private function createBotUserWithAvatarButInInvisibleMode(InternalTelegramUserId $telegramUserId, Gender $gender, Gender $preferredGender, int $seenQty, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'telegram_id' => $telegramUserId->value(),
                    'status' => (new Registered())->value(),

                    'gender' => $gender->value(),
                    'preferred_gender' => $preferredGender->value(),
                    'user_mode' => (new Invisible())->value(),

                    'has_avatar' => 1,
                    'seen_qty' => $seenQty
                ]
            ]);
    }

    private function createBotUserWithoutAvatar(InternalTelegramUserId $telegramUserId, Gender $gender, Gender $preferredGender, int $seenQty, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => 'some name',
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => 'some handle',
                    'status' => (new Registered())->value(),

                    'gender' => $gender->value(),
                    'preferred_gender' => $preferredGender->value(),

                    'has_avatar' => 0,
                    'seen_qty' => $seenQty,
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

    private function userReply(InternalTelegramUserId $voterTelegramId, RateCallbackData $callbackData, HttpTransport $transport, OpenConnection $connection)
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

    private function assertUserIsInVisibleMode(InternalTelegramUserId $voterTelegramId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(new ByInternalTelegramUserId($voterTelegramId, $connection)))
                ->equals(
                    new FromPure(new Visible())
                )
        );
    }

    private function assertUserIsInInvisibleMode(InternalTelegramUserId $voterTelegramId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(new ByInternalTelegramUserId($voterTelegramId, $connection)))
                ->equals(
                    new FromPure(new Invisible())
                )
        );
    }


    private function assertPairPersisted(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $firstPairTelegramId, Reaction $reaction, OpenConnection $connection)
    {
        $pair = new ByVoterTelegramIdAndRatedTelegramId($recipientTelegramId, $firstPairTelegramId, $connection);

        $this->assertTrue((new FromViewedPair($pair))->equals($reaction));
    }
}