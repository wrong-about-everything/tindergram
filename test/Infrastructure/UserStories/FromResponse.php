<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\UserStories;

use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;

class FromResponse extends Existent
{
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function response(): Response
    {
        return $this->response;
    }
}