<?php

declare(strict_types=1);

namespace TG\Activities;

use TG\Infrastructure\Http\Request\Url\Query;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\UserStory\Body\Arrray;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\Successful;

class Sample extends Existent
{
    private $id;
    private $name;
    private $query;
    private $logs;

    public function __construct(string $id, string $name, Query $query, Logs $logs)
    {
        $this->id = $id;
        $this->name = $name;
        $this->query = $query;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Sample scenario started'));

        $this->logs->receive(new InformationMessage('Sample scenario finished'));
        return
            new Successful(
                new Arrray([
                    'id' => $this->id,
                    'name' => $this->name,
                ])
            );
    }
}