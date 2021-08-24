<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\UserStories;

use Exception;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;

class ThrowingException extends Existent
{
    public function response(): Response
    {
        throw new Exception('Oh shit!');
    }
}