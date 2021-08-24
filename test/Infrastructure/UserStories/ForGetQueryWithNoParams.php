<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\UserStories;

use RC\Infrastructure\UserStory\Body\Arrray;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class ForGetQueryWithNoParams extends Existent
{
    public function response(): Response
    {
        return new Successful(new Arrray(['hello' => 'vasya']));
    }
}