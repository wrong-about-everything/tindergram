<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response\RestfulHttp;

use TG\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService\HttpResponseHeaderFromUserStoryResponseHeader;
use TG\Infrastructure\Http\Response\Header;
use TG\Infrastructure\UserStory\Header as UserStoryResponseHeader;

class HttpHeaderFromUserStoryHeader extends Header
{
    private $httpHeader;

    public function __construct(UserStoryResponseHeader $userStoryResponseHeader)
    {
        $this->httpHeader = new HttpResponseHeaderFromUserStoryResponseHeader($userStoryResponseHeader);
    }

    public function value(): string
    {
        return $this->httpHeader->value();
    }

    public function exists(): bool
    {
        return $this->httpHeader->exists();
    }
}