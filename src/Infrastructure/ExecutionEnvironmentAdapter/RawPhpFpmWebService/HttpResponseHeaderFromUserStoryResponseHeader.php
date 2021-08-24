<?php

declare(strict_types=1);

namespace TG\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;

use TG\Infrastructure\Http\Response\Header;
use TG\Infrastructure\Http\Response\Header\NonExistent;
use TG\Infrastructure\UserStory\Header as UserStoryResponseHeader;

class HttpResponseHeaderFromUserStoryResponseHeader extends Header
{
    private $userStoryResponseHeader;

    public function __construct(UserStoryResponseHeader $userStoryResponseHeader)
    {
        $this->userStoryResponseHeader = $userStoryResponseHeader->isHttpSpecific() ? $userStoryResponseHeader : new NonExistent();
    }

    public function value(): string
    {
        return $this->userStoryResponseHeader->value();
    }

    public function exists(): bool
    {
        return true;
    }
}