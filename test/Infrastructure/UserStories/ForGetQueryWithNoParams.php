<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\UserStories;

use TG\Infrastructure\UserStory\Body\Arrray;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class ForGetQueryWithNoParams extends Existent
{
    public function response(): Response
    {
        return new Successful(new Arrray(['hello' => 'vasya']));
    }
}