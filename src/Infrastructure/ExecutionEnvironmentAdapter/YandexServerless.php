<?php

declare(strict_types=1);

namespace TG\Infrastructure\ExecutionEnvironmentAdapter;

use Exception;
use TG\Infrastructure\Logging\LogItem\FromThrowable;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\UserStory\LazySafetyNet;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\RestfulHttp\FromUserStoryResponse;
use TG\Infrastructure\UserStory\UserStory;
use Throwable;

class YandexServerless
{
    private $userStory;

    public function __construct(UserStory $userStory, Response $fallbackResponse, Logs $logs)
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline, array $errcontex) {
                throw new Exception($errstr, 0);
            },
            E_ALL
        );
        set_exception_handler(
            function (Throwable $throwable) use ($logs) {
                $logs->receive(new FromThrowable($throwable));
                throw $throwable;
            }
        );

        $this->userStory = new LazySafetyNet($userStory, $fallbackResponse, $logs);
    }

    public function response(): array
    {
        $httpResponse = new FromUserStoryResponse($this->userStory->response());
        return [
            'statusCode' => $httpResponse->code()->value(),
            'body' => $httpResponse->body(),
        ];
    }
}