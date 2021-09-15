<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\TelegramBot\UserAvatars\OutboundModel;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Response\Code\BadRequest;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromArray;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;
use TG\Tests\Infrastructure\Http\Transport\FakeWithResponse;

class SentToUserTest extends TestCase
{
    public function testNoneOfAvatarsNoLongerExist()
    {
        $httpTransport = $this->noneOfAvatarsNoLongerExist();

        $value =
            (new SentToUser(
                new FromString('hello'),
                new FromArray(['avatar_1', 'avatar_2', 'avatar_3', ]),
                new FromInteger(111111),
                $httpTransport
            ))
                ->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(3, $httpTransport->sentRequests());

    }

    private function noneOfAvatarsNoLongerExist()
    {
        return
            new FakeWithResponse(
                new DefaultResponse(
                    new BadRequest(),
                    [],
                    '{"ok":false,"error_code":400,"description":"Bad Request: FILE_REFERENCE_0_EXPIRED"}'
                )
            );
    }
}