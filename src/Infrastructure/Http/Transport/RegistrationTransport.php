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

class RegistrationTransport implements FakeTransport
{
    private $requests;

    public function __construct()
    {
        $this->requests = [];
    }

    public function response(Request $request): Response
    {
        $eagerlyInvoked = new EagerlyInvoked($request);
        $this->requests[] = $eagerlyInvoked;
        switch ((new FromUrl($request->url()))->value()) {
            case (new SendMessage())->value():
                return new DefaultResponse(new Ok(), [], '');

            case (new SendMediaGroup())->value():
            case (new GetUserProfilePhotos())->value():
                return
                    new DefaultResponse(
                        new Ok(),
                        [],
                        json_encode([
                            'result' => [
                                'photos' => [
                                    [['file_id' => 'vasya']],
                                    [['file_id' => 'you look so fine']],
                                ]
                            ]
                        ])
                    );

            case (new GetFile())->value():
                return
                    new DefaultResponse(
                        new Ok(),
                        [],
                        json_encode([
                            'result' => [
                                'file_id' => 'vasya'
                            ]
                        ])
                    );

        }

        return new DefaultResponse(new Ok(), [], '');
    }

    /**
     * @return Request[]
     */
    public function sentRequests(): array
    {
        return $this->requests;
    }
}
