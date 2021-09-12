<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Domain\ViewedPair\WriteModel;

use PHPUnit\Framework\TestCase;
use TG\Domain\Pair\WriteModel\SentByHttp;
use TG\Infrastructure\Http\Transport\TransportWithNoAvatars;
use TG\Infrastructure\Http\Transport\TransportWithNAvatars;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class SentTest extends TestCase
{
    public function testWhenPairHasNoPhotosThenOnlyInfoIsSent()
    {
        $httpTransport = new TransportWithNoAvatars();
        $value = (new SentByHttp($this->recipientTelegramId(), $this->pairTelegramId(), 'Vasya', $httpTransport))->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(
            1/*get user avatars*/ + 1/*send pair info with keyboard*/,
            $httpTransport->sentRequests()
        );
    }

    public function testWhenPairHasMoreThanFivePhotosThenOnlyFiveAreSent()
    {
        $httpTransport = new TransportWithNAvatars(654);
        $value = (new SentByHttp($this->recipientTelegramId(), $this->pairTelegramId(), 'Vasya', $httpTransport))->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(
            1/*get user avatars*/ + 5/*get file info*/ + 1/*send media*/ + 1/*send pair info with keyboard*/,
            $httpTransport->sentRequests()
        );
    }

    private function recipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(1111111111);
    }

    private function pairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(22222222);
    }
}