<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\UserStories;

use TG\Infrastructure\Http\Request\Url\Query;
use TG\Infrastructure\UserStory\Body\Arrray;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class ForGetQueryWithParams extends Existent
{
    private $id;
    private $name;
    private $query;

    public function __construct(string $id, string $name, Query $query)
    {
        $this->id = $id;
        $this->name = $name;
        $this->query = $query;
    }

    public function response(): Response
    {
        return new Successful(new Arrray(['id' => $this->id, 'name' => $this->name]));
    }
}