<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response\RestfulHttp;

use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Outbound\Response;
use TG\Infrastructure\UserStory\Header;
use TG\Infrastructure\UserStory\Response as UserStoryResponse;

class FromUserStoryResponse implements Response
{
    private $userStoryResponse;

    public function __construct(UserStoryResponse $userStoryResponse)
    {
        $this->userStoryResponse = $userStoryResponse;
    }

    public function code(): Code
    {
        return new HttpCodeFromUserStoryCode($this->userStoryResponse->code());
    }

    public function headers(): array/*Header[]*/
    {
        return
            array_map(
                function (Header $userStoryHeader) {
                    return new HttpHeaderFromUserStoryHeader($userStoryHeader);
                },
                array_filter(
                    $this->userStoryResponse->headers(),
                    function (Header $userStoryHeader) {
                        return $userStoryHeader->isHttpSpecific();
                    }
                )
            );
    }

    public function body(): string
    {
        return
            $this->userStoryResponse->body()->isPresent()
                ? json_encode($this->userStoryResponse->body()->raw())
                : '';
    }
}