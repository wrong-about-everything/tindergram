<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\InboundModel;

use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Inbound\Response;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\Method\GetFile;

class NonDeleted implements UserAvatarIds
{
    private $avatarsOf;
    private $userAvatars;
    private $httpTransport;
    private $cached;

    public function __construct(InternalTelegramUserId $avatarsOf, UserAvatarIds $userAvatars, HttpTransport $httpTransport)
    {
        $this->avatarsOf = $avatarsOf;
        $this->userAvatars = $userAvatars;
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
        $userAvatars = $this->userAvatars->value();
        if (!$userAvatars->isSuccessful()) {
            return $userAvatars;
        }

        return
            new Successful(
                new Present(
                    array_map(
                        function (Response $response) {
                            return json_decode($response->body(), true)['result']['file_id'];
                        },
                        array_values(
                            array_filter(
                                array_map(
                                    function (string $fileId) {
                                        return
                                            $this->httpTransport
                                                ->response(
                                                    new OutboundRequest(
                                                        new Post(),
                                                        new BotApiUrl(
                                                            new GetFile(),
                                                            new FromArray([
                                                                'chat_id' => $this->avatarsOf->value(),
                                                                'file_id' => $fileId
                                                            ])
                                                        ),
                                                        [],
                                                        ''
                                                    )
                                                );
                                    },
                                    $userAvatars->pure()->raw()
                                ),
                                function (Response $response) {
                                    return $response->code()->equals(new Ok());
                                }
                            )
                        )
                    )
                )
            );
    }
}