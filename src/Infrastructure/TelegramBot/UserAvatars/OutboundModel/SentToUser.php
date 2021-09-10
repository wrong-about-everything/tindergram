<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel;

use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Outbound\OutboundRequest;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\Error\AlarmDeclineWithDefaultUserMessage;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\UserAvatarIds as InboundModelUserAvatars;

class SentToUser implements UserAvatars
{
    private $message;
    private $avatarsOfUser;
    private $sentTo;
    private $httpTransport;

    public function __construct(MessageToUser $message, InboundModelUserAvatars $avatarsOfUser, InternalTelegramUserId $sendTo, HttpTransport $httpTransport)
    {
        $this->message = $message;
        $this->avatarsOfUser = $avatarsOfUser;
        $this->sentTo = $sendTo;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        if (!$this->avatarsOfUser->value()->isSuccessful()) {
            return $this->avatarsOfUser->value();
        }
        if (count($this->avatarsOfUser->value()->pure()->raw()) === 0) {
            return new Successful(new Emptie());
        }

        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMediaGroup(),
                            new FromArray([
                                'chat_id' => $this->sentTo->value(),
                                'media' =>
                                    json_encode(
                                        array_reduce(
                                            array_map(
                                                function (string $fileId) {
                                                    return ['type' => 'photo', 'media' => $fileId];
                                                },
                                                $this->avatarsOfUser->value()->pure()->raw()
                                            ),
                                            function (array $photos, array $photoBlock) {
                                                if ($this->message->isNonEmpty() && empty($photos)) {
                                                    return [array_merge($photoBlock, ['caption' => $this->message->value()])];
                                                }

                                                return array_merge($photos, [$photoBlock]);
                                            },
                                            []
                                        )
                                    ),
                            ])
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new AlarmDeclineWithDefaultUserMessage('sendMediaGroup response is not successful', []));
        }

        return new Successful(new Emptie());
    }
}