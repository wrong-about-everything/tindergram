<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory\Response\RestfulHttp;

use Exception;
use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Code\BadRequest;
use TG\Infrastructure\Http\Response\Code\Ok;
use TG\Infrastructure\Http\Response\Code\NonRetryableServerError as NonRetryableHttpServerError;
use TG\Infrastructure\Http\Response\Code\RetryableServerError as RetryableHttpServerError;
use TG\Infrastructure\Http\Response\Code\Unauthorized as UnauthorizedHttpCode;
use TG\Infrastructure\UserStory\Code as UserStoryCode;
use TG\Infrastructure\UserStory\Code\ClientRequestError;
use TG\Infrastructure\UserStory\Code\NonRetryableServerError;
use TG\Infrastructure\UserStory\Code\RetryableServerError;
use TG\Infrastructure\UserStory\Code\Successful;
use TG\Infrastructure\UserStory\Code\Unauthorized;

class HttpCodeFromUserStoryCode extends Code
{
    private $userStoryCode;

    public function __construct(UserStoryCode $userStoryCode)
    {
        $this->userStoryCode = $userStoryCode;
    }

    public function value(): int
    {
        if ($this->userStoryCode->equals(new Successful())) {
            return (new Ok())->value();
        } elseif ($this->userStoryCode->equals(new RetryableServerError())) {
            return (new RetryableHttpServerError())->value();
        } elseif ($this->userStoryCode->equals(new NonRetryableServerError())) {
            return (new NonRetryableHttpServerError())->value();
        } elseif ($this->userStoryCode->equals(new ClientRequestError())) {
            return (new BadRequest())->value();
        } elseif ($this->userStoryCode->equals(new Unauthorized())) {
            return (new UnauthorizedHttpCode())->value();
        }

        throw new Exception(sprintf('Unknown user story code given: %s', $this->userStoryCode->value()));
    }
}