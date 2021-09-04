<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Meringue\Timeline\Point\Now;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TG\Activities\Cron\ShowsPair\ShowsPair;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\UserStory\Authorized;
use TG\Domain\UserStory\Body\TelegramFallbackResponseBody;
use TG\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use TG\Infrastructure\ExecutionEnvironmentAdapter\GoogleServerless;
use TG\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString as ExistentDirPathFromAbsolutePathString;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString as ExistentFilePathFromAbsolutePathString;
use TG\Infrastructure\Filesystem\FilePath\ExistentFromDirAndFileName;
use TG\Infrastructure\Http\Request\Inbound\DefaultInbound;
use TG\Infrastructure\Http\Request\Inbound\FromPsrHttpRequest;
use TG\Infrastructure\Http\Request\Inbound\WithPathTakenFromQueryParam;
use TG\Infrastructure\Http\Request\Method\Get;
use TG\Infrastructure\Http\Request\Method\Post;
use TG\Infrastructure\Http\Request\Url\Query;
use TG\Infrastructure\Http\Transport\EnvironmentDependentTransport;
use TG\Infrastructure\Logging\LogId;
use TG\Infrastructure\Logging\Logs\EnvironmentDependentLogs;
use TG\Infrastructure\Logging\Logs\File;
use TG\Infrastructure\Logging\Logs\GoogleCloudLogs;
use TG\Infrastructure\Routing\Route\ArbitraryTelegramUserMessageRoute;
use TG\Infrastructure\Routing\Route\ArbitraryTelegramUserMessageRouteWithBotId;
use TG\Infrastructure\Routing\Route\MatchingAnyPostRequest;
use TG\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use TG\Infrastructure\Routing\Route\RouteByTelegramBotCommand;
use TG\Infrastructure\TelegramBot\UserCommand\Start;
use TG\Infrastructure\UserStory\ByRoute;
use TG\Infrastructure\UserStory\Response\Successful;
use TG\Infrastructure\Uuid\RandomUUID;
use TG\Activities\Sample;
use TG\Activities\SomeoneSentUnknownPostRequest;
use TG\UserActions\PressesStart\PressesStart;
use TG\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

(new EnvironmentDependentEnvFile(
    new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
    new DefaultInbound()
))
    ->load();

function entryPoint(ServerRequestInterface $request): ResponseInterface
{
    $logs =
        new EnvironmentDependentLogs(
            new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
            new File(
                new ExistentFromDirAndFileName(
                    new FromNestedDirectoryNames(
                        new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)),
                        new PortableFromString('logs')
                    ),
                    new PortableFromString('log.json')
                ),
                new LogId(new RandomUUID())
            ),
            new GoogleCloudLogs(
                'lyrical-bolt-318307',
                'cloudfunctions.googleapis.com%2Fcloud-functions',
                new ExistentFilePathFromAbsolutePathString(__DIR__ . '/deploy/lyrical-bolt-318307-a42c68ffa3c8.json'),
                new LogId(new RandomUUID())
            )
        );
    $transport = new EnvironmentDependentTransport(new ExistentDirPathFromAbsolutePathString(dirname(__FILE__)), $logs);

    return
        (new GoogleServerless(
            new Authorized(
                new ByRoute(
                    [
                        [
                            new RouteByMethodAndPathPattern(
                                new Get(),
                                '/hello/:id/world/:name'
                            ),
                            function (string $id, string $name, Query $query) use ($logs) {
                                return new Sample($id, $name, $query, $logs);
                            }
                        ],
                        [
                            new RouteByTelegramBotCommand(new Start()),
                            function (array $parsedTelegramMessage) use ($transport, $logs) {
                                return new PressesStart($parsedTelegramMessage, $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new ArbitraryTelegramUserMessageRoute(),
                            function (array $parsedTelegramMessage) use ($transport, $logs) {
                                return new SendsArbitraryMessage($parsedTelegramMessage, $transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            new RouteByMethodAndPathPattern(
                                new Post(),
                                '/cron/shows_pair'
                            ),
                            function () use ($transport, $logs) {
                                return new ShowsPair($transport, new ApplicationConnection(), $logs);
                            }
                        ],
                        [
                            // this one must go the last
                            new MatchingAnyPostRequest(),
                            function (string $message) use ($logs) {
                                return new SomeoneSentUnknownPostRequest($message, $logs);
                            }
                        ],
                    ],
                    new WithPathTakenFromQueryParam(
                        'ad_hoc_path',
                        new FromPsrHttpRequest($request)
                    )
                ),
                new FromPsrHttpRequest($request)
            ),
            $request,
            new Successful(new TelegramFallbackResponseBody()),
            $logs
        ))
            ->response();
}
