<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\Method\GetUserProfilePhotos;

class FromTelegram implements UserAvatarIds
{
    private $telegramUserId;
    private $httpTransport;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->httpTransport = $httpTransport;
        $this->cached = null;
    }

    public function value(): ImpureValue/*array*/
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $response = $this->response();
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new AlarmDeclineWithDefaultUserMessage('getUserProfilePhotos request is not successful', []));
        }

        return
            new Successful(
                new Present(
                    array_map(
                        function (array $threeSizesOfOnePhoto) {
                            return $threeSizesOfOnePhoto[0]['file_id'];
                        },
                        json_decode(
                            $response->body(),
                            true
                        )['result']['photos']
                    )
                )
            );
    }

    private function response(): Response
    {
        return
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new GetUserProfilePhotos(),
                            new FromArray([
                                'user_id' => $this->telegramUserId->value(),
                            ])
                        ),
                        [],
                        ''
                    )
                );
    }
}