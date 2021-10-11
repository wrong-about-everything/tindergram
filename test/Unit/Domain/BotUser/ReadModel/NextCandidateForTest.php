<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Domain\BotUser\ReadModel;

use PHPUnit\Framework\TestCase;
use TG\Domain\BotUser\ReadModel\NextCandidateFor;
use TG\Domain\BotUser\UserStatus\Pure\InactiveAfterRegistered;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Domain\Gender\Pure\Female;
use TG\Domain\Gender\Pure\Male;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\UserMode\Pure\Invisible;
use TG\Domain\UserMode\Pure\Visible;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\ViewedPair;

class NextCandidateForTest extends TestCase
{
    public function testWhenNotYetViewedRegisteredCandidateIsVisibleAndHasAvatarAndAccountIsNotPausedThenHeIsANextPair()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar($this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertTrue($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenNotYetViewedRegisteredCandidateIsInvisibleAndHasAvatarAndAccountIsNotPausedThenHeIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsInvisibleAndActiveAndWithAvatar($this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenNotYetViewedRegisteredCandidateIsVisibleAndDoesNotHaveAvatarAndAccountIsNotPausedThenHeIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithoutAvatar($this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenNotYetViewedRegisteredCandidateIsVisibleAndHasAnAvatarAndAccountIsPausedThenHeIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsVisibleAndInactiveAndWithAvatar($this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenNotYetViewedCandidateIsNotRegisteredThenHeIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedNonRegisteredMalePreferringFemales($this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenCandidateIsViewedButNotRatedThenHeIsANextCandidate()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar($this->candidateTelegramUserId(), $connection);
        $this->seedNotRatedPair($this->recipientTelegramId(), $this->candidateTelegramUserId(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertTrue($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenCandidateIsViewedAndRatedThenHeIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar($this->candidateTelegramUserId(), $connection);
        $this->seedRatedPair($this->recipientTelegramId(), $this->candidateTelegramUserId(), new Like(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    public function testWhenRecipientIsMalePreferringFemalesThenFemaleCandidatePreferringFemalesIsSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedRegisteredFemalePreferringMales($this->recipientTelegramId(), $connection);
        $this->seedRegisteredFemalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar($this->candidateTelegramUserId(), $connection);
        $this->seedRatedPair($this->recipientTelegramId(), $this->candidateTelegramUserId(), new Like(), $connection);

        $nextCandidate = new NextCandidateFor($this->recipientTelegramId(), $connection);

        $this->assertFalse($nextCandidate->value()->pure()->isPresent());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Male())->value(),
                    'status' => (new Registered())->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 1
                ]
            ]);
    }

    private function seedRegisteredFemalePreferringFemalesWhichIsVisibleAndActiveAndWithAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Female())->value(),
                    'status' => (new Registered())->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 1
                ]
            ]);
    }

    private function seedNotRatedPair(InternalTelegramUserId $recipientTelegramUserId, InternalTelegramUserId $pairTelegramUserId, OpenConnection $connection)
    {
        (new ViewedPair($connection))
            ->insert([
                [
                    'recipient_telegram_id' => $recipientTelegramUserId->value(),
                    'pair_telegram_id' => $pairTelegramUserId->value(),
                    'reaction' => null
                ]
            ]);
    }

    private function seedRatedPair(InternalTelegramUserId $recipientTelegramUserId, InternalTelegramUserId $pairTelegramUserId, Reaction $reaction, OpenConnection $connection)
    {
        (new ViewedPair($connection))
            ->insert([
                [
                    'recipient_telegram_id' => $recipientTelegramUserId->value(),
                    'pair_telegram_id' => $pairTelegramUserId->value(),
                    'reaction' => $reaction->value()
                ]
            ]);
    }

    private function seedNonRegisteredMalePreferringFemales(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Male())->value(),
                    'status' => (new RegistrationIsInProgress())->value(),
                    'user_mode' => null,

                    'has_avatar' => null
                ]
            ]);
    }

    private function seedRegisteredMalePreferringFemalesWhichIsVisibleAndInactiveAndWithAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Male())->value(),
                    'status' => (new InactiveAfterRegistered())->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 1
                ]
            ]);
    }

    private function seedRegisteredMalePreferringFemalesWhichIsVisibleAndActiveAndWithoutAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Male())->value(),
                    'status' => (new Registered())->value(),
                    'user_mode' => (new Visible())->value(),

                    'has_avatar' => 0
                ]
            ]);
    }

    private function seedRegisteredMalePreferringFemalesWhichIsInvisibleAndActiveAndWithAvatar(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Female())->value(),
                    'gender' => (new Male())->value(),
                    'status' => (new Registered())->value(),
                    'user_mode' => (new Invisible())->value(),

                    'has_avatar' => 1
                ]
            ]);
    }

    private function seedRegisteredFemalePreferringMales(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'telegram_id' => $telegramUserId->value(),

                    'preferred_gender' => (new Male())->value(),
                    'gender' => (new Female())->value(),
                ]
            ]);
    }

    private function candidateTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(123456);
    }

    private function recipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(987);
    }
}