<?php

declare(strict_types=1);

namespace TG\Activities;

use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\RetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;

class SomeoneSentUnknownPostRequest extends Existent
{
    private $body;
    private $logs;

    public function __construct(string $body, Logs $logs)
    {
        $this->body = $body;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        if ($this->body === '') {
            $this->logs->receive(new InformationMessage('Someone sent unknown POST request with empty body'));
            return new RetryableServerError(new Emptie());
        }

        $this->logs->receive(new InformationMessage(sprintf('Someone sent unknown POST request with body %s', $this->body)));
        return new Successful(new Emptie());
    }
}