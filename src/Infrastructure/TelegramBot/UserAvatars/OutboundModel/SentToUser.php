<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\UserAvatars\OutboundModel;

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
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\TelegramBot\BotApiUrl;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;
use TG\Infrastructure\TelegramBot\Method\SendMediaGroup;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\AllAvatarsExcluding;
use TG\Infrastructure\TelegramBot\UserAvatars\InboundModel\UserAvatarIds as InboundModelUserAvatars;

class SentToUser implements UserAvatars
{
    private $message;
    private $avatarsOfUser;
    private $sendTo;
    private $httpTransport;

    public function __construct(MessageToUser $message, InboundModelUserAvatars $avatarsOfUser, InternalTelegramUserId $sendTo, HttpTransport $httpTransport)
    {
        $this->message = $message;
        $this->avatarsOfUser = $avatarsOfUser;
        $this->sendTo = $sendTo;
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
                                'chat_id' => $this->sendTo->value(),
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
        if (!$response->isAvailable()) {
            return new Failed(new AlarmDeclineWithDefaultUserMessage('sendMediaGroup response is not successful', []));
        } elseif ($this->someAvatarIsInvalid($response)) {
            return
                (new SentToUser(
                    $this->message,
                    new AllAvatarsExcluding($this->avatarsOfUser, $this->invalidAvatarIndex($response)),
                    $this->sendTo,
                    $this->httpTransport
                ))
                    ->value();
        } elseif (!$response->code()->equals(new Ok())) {
            return new Failed(new AlarmDeclineWithDefaultUserMessage('sendMediaGroup response is not successful', []));
        }

        return new Successful(new Emptie());
    }

    private function someAvatarIsInvalid(Response $response): bool
    {
        if (json_decode($response->body(), true)['ok']) {
            return false;
        }

        return strpos(json_decode($response->body(), true)['description'], 'Bad Request: FILE_REFERENCE_') !== false;
    }

    private function invalidAvatarIndex(Response $response): int
    {
        return (int) substr(json_decode($response->body(), true)['description'], 0, strlen('Bad Request: FILE_REFERENCE_'))[0];
    }
}