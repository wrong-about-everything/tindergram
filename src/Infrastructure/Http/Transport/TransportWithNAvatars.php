<?php

declare(strict_types = 1);

namespace TG\Infrastructure\Http\Transport;

use TG\Infrastructure\Http\Request\Outbound\EagerlyInvoked;
use TG\Infrastructure\Http\Request\Outbound\Request;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Response\Inbound\DefaultResponse;
use TG\Infrastructure\TelegramBot\Method\FromUrl;
use TG\Infrastructure\TelegramBot\Method\GetFile;
use TG\Infrastructure\TelegramBot\Method\GetUserProfilePhotos;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\Method\SendMessage;
use TG\Tests\Infrastructure\Http\Transport\FakeTransport;

class TransportWithNAvatars implements FakeTransport
{
    private $amountOfAvatars;
    private $requests;

    public function __construct(int $amountOfAvatars)
    {
        $this->amountOfAvatars = $amountOfAvatars;
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $eagerlyInvoked = new EagerlyInvoked($request);
        $this->requests[] = $eagerlyInvoked;
        switch ((new FromUrl($request->url()))->value()) {
            case (new SendMessage())->value():
                return new DefaultResponse(new Ok(), [], json_encode(['ok' => true]));

            case (new SendMediaGroup())->value():
            case (new GetUserProfilePhotos())->value():
                return
                    new DefaultResponse(
                        new Ok(),
                        [],
                        json_encode([
                            'ok' => true,
                            'result' => [
                                'photos' =>
                                    array_fill(
                                        0,
                                        $this->amountOfAvatars,
                                        [['file_id' => 'vasya']]
                                    )
                            ]
                        ])
                    );

            case (new GetFile())->value():
                return
                    new DefaultResponse(
                        new Ok(),
                        [],
                        json_encode([
                            'ok' => true,
                            'result' => [
                                'file_id' => 'vasya'
                            ]
                        ])
                    );

        }

        return new DefaultResponse(new Ok(), [], json_encode(['ok' => true]));
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
