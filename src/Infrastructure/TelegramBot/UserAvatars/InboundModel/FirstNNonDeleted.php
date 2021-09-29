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

/**
 * This class doesn't seem to bring any value: getFile method might succeed on an absent avatar.
 */
class FirstNNonDeleted implements UserAvatarIds
{
    private $avatarsOf;
    private $userAvatars;
    private $httpTransport;
    private $avatarsAmount;
    private $cached;

    public function __construct(InternalTelegramUserId $avatarsOf, UserAvatarIds $userAvatars, int $avatarsAmount, HttpTransport $httpTransport)
    {
        $this->avatarsOf = $avatarsOf;
        $this->userAvatars = $userAvatars;
        $this->httpTransport = $httpTransport;
        $this->avatarsAmount = $avatarsAmount;
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
                            $this->firstNSuccessfulGetFileResponses($userAvatars->pure()->raw())
                        )
                    )
                )
            );
    }

    private function firstNSuccessfulGetFileResponses(array $userAvatars): array
    {
        return $this->firstNSuccessfulGetFileResponsesIteration($userAvatars, []);
    }

    private function firstNSuccessfulGetFileResponsesIteration(array $userAvatarIds, array $successfulGetFileResponses): array
    {
        if (count($successfulGetFileResponses) === $this->avatarsAmount || empty($userAvatarIds)) {
            return $successfulGetFileResponses;
        }

        $firstAvatarId = array_shift($userAvatarIds);
        $getFileResponse =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new GetFile(),
                            new FromArray([
                                'chat_id' => $this->avatarsOf->value(),
                                'file_id' => $firstAvatarId
                            ])
                        ),
                        [],
                        ''
                    )
                );

        return
            $this->firstNSuccessfulGetFileResponsesIteration(
                $userAvatarIds,
                array_merge(
                    $successfulGetFileResponses,
                    $getFileResponse->isAvailable() && $getFileResponse->code()->equals(new Ok())
                        ? [$getFileResponse]
                        : []
                )
            );
    }
}