<?php

declare(strict_types=1);

namespace TG\Domain\UserStory;

use TG\Infrastructure\Http\Request\Inbound\Request;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery as ParsedQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Unauthorized;
use TG\Infrastructure\UserStory\UserStory;

class Authorized extends Existent
{
    private $original;
    private $request;

    public function __construct(UserStory $original, Request $request)
    {
        $this->original = $original;
        $this->request = $request;
    }

    public function response(): Response
    {
        if (
            !isset((new ParsedQuery(new FromUrl($this->request->url())))->value()['secret_smile'])
                ||
            (new ParsedQuery(new FromUrl($this->request->url())))->value()['secret_smile'] !== getenv('SECRET_SMILE')
        ) {
            return new Unauthorized();
        }

        return $this->original->response();
    }
}