<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\TelegramBot\UserAvatars\OutboundModel;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Response\Code\BadRequest;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\MessageToUser\FromString;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\FromArray;
use TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel\SentToUser;
use TG\Tests\Infrastructure\Http\Response\Inbound\EmptySuccessfulResponse;
use TG\Tests\Infrastructure\Http\Transport\ConfiguredByTelegramMethodAndInvocationCount;

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
        $this->assertCount(4, $httpTransport->sentRequests());
        $this->assertEquals(
            [
                [
                    'type' => 'photo',
                    'media' => 'https://storage.googleapis.com/51d3a3b0-a6e0-41f2-ab53-7ef0495996d0/no_photo_gray.png',
                    'caption' => 'hello'
                ]
            ],
            json_decode((new FromQuery(new FromUrl($httpTransport->sentRequests()[3]->url())))->value()['media'], true)
        );
    }

    public function testFirstAvatarNoLongerExists()
    {
        $httpTransport = $this->nthAvatarNoLongerExists(0);

        $value =
            (new SentToUser(
                new FromString('hello'),
                new FromArray(['avatar_1', 'avatar_2', 'avatar_3', ]),
                new FromInteger(111111),
                $httpTransport
            ))
                ->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(2, $httpTransport->sentRequests());
        $this->assertEquals(
            [
                [
                    'type' => 'photo',
                    'media' => 'avatar_2',
                    'caption' => 'hello'
                ],
                [
                    'type' => 'photo',
                    'media' => 'avatar_3',
                ],
            ],
            json_decode((new FromQuery(new FromUrl($httpTransport->sentRequests()[1]->url())))->value()['media'], true)
        );
    }

    public function testSecondAvatarNoLongerExists()
    {
        $httpTransport = $this->nthAvatarNoLongerExists(1);

        $value =
            (new SentToUser(
                new FromString('hello'),
                new FromArray(['avatar_1', 'avatar_2', 'avatar_3', ]),
                new FromInteger(111111),
                $httpTransport
            ))
                ->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(2, $httpTransport->sentRequests());
        $this->assertEquals(
            [
                [
                    'type' => 'photo',
                    'media' => 'avatar_1',
                    'caption' => 'hello'
                ],
                [
                    'type' => 'photo',
                    'media' => 'avatar_3',
                ],
            ],
            json_decode((new FromQuery(new FromUrl($httpTransport->sentRequests()[1]->url())))->value()['media'], true)
        );
    }

    public function testThirdAvatarNoLongerExists()
    {
        $httpTransport = $this->nthAvatarNoLongerExists(2);

        $value =
            (new SentToUser(
                new FromString('hello'),
                new FromArray(['avatar_1', 'avatar_2', 'avatar_3', ]),
                new FromInteger(111111),
                $httpTransport
            ))
                ->value();

        $this->assertTrue($value->isSuccessful());
        $this->assertCount(2, $httpTransport->sentRequests());
        $this->assertEquals(
            [
                [
                    'type' => 'photo',
                    'media' => 'avatar_1',
                    'caption' => 'hello'
                ],
                [
                    'type' => 'photo',
                    'media' => 'avatar_2',
                ],
            ],
            json_decode((new FromQuery(new FromUrl($httpTransport->sentRequests()[1]->url())))->value()['media'], true)
        );
    }

    private function noneOfAvatarsNoLongerExist()
    {
        return
            new ConfiguredByTelegramMethodAndInvocationCount([
                (new SendMediaGroup())->value() => [
                    0 => $this->firstAvatarExpired(),
                    1 => $this->firstAvatarExpired(),
                    2 => $this->firstAvatarExpired(),
                    3 => new EmptySuccessfulResponse()
                ]
            ]);

    }

    private function nthAvatarNoLongerExists(int $nthExpiredAvatar)
    {
        return
            new ConfiguredByTelegramMethodAndInvocationCount([
                (new SendMediaGroup())->value() => [
                    0 => $this->nthAvatarExpired($nthExpiredAvatar),
                    1 => new EmptySuccessfulResponse(),
                ]
            ]);

    }

    private function firstAvatarExpired(): Response
    {
        return $this->nthAvatarExpired(0);
    }

    private function nthAvatarExpired(int $nth)
    {
        return
            new DefaultResponse(
                new BadRequest(),
                [],
                sprintf('{"ok":false,"error_code":400,"description":"Bad Request: FILE_REFERENCE_%s_EXPIRED"}', $nth)
            );
    }
}