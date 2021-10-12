<?php

declare(strict_types=1);

namespace TG\Activities\Cron\SendNewCandidatesToUsers;

use Meringue\ISO8601DateTime;
use TG\Domain\BotUser\ReadModel\NextCandidateFor;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\Pair\WriteModel\SentIfExistsNothingOtherwise;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use TG\Infrastructure\Logging\LogItem\InformationMessage;
use TG\Infrastructure\Logging\Logs;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromBotUserDatabaseRecord;
use TG\Infrastructure\UserStory\Body\Emptie;
use TG\Infrastructure\UserStory\Existent;
use TG\Infrastructure\UserStory\Response;
use TG\Infrastructure\UserStory\Response\NonRetryableServerError;
use TG\Infrastructure\UserStory\Response\Successful;

class SendNewCandidatesToUsersWhoHaveMadeItToTheEnd extends Existent
{
    private $now;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(ISO8601DateTime $now, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->now = $now;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Send new candidates to users scenario started'));

        $usersWhoHaveMadeItToTheEndAWhileAgo = $this->usersWhoHaveMadeItToTheEndAWhileAgo();
        if (!$usersWhoHaveMadeItToTheEndAWhileAgo->isSuccessful()) {
            $this->logs->receive(new FromNonSuccessfulImpureValue($usersWhoHaveMadeItToTheEndAWhileAgo));
            return new NonRetryableServerError(new Emptie());
        }

        array_map(
            function (array $botUser) {
                $sentPair =
                    (new SentIfExistsNothingOtherwise(
                        new NextCandidateFor(
                            new FromBotUserDatabaseRecord($botUser),
                            $this->connection
                        ),
                        new FromBotUserDatabaseRecord($botUser),
                        $this->transport,
                        $this->connection
                    ))
                        ->value();
                if (!$sentPair->isSuccessful()) {
                    $this->logs->receive(new FromNonSuccessfulImpureValue($sentPair));
                }
            },
            $usersWhoHaveMadeItToTheEndAWhileAgo->pure()->raw()
        );

        $this->logs->receive(new InformationMessage('Send new candidates to users scenario finished'));

        return new Successful(new Emptie());
    }

    private function usersWhoHaveMadeItToTheEndAWhileAgo(): ImpureValue
    {
        return
            (new Selecting(
                <<<qqqqqqqqqq
select *
from (
    select distinct
        bu.telegram_id,
        first_value(vp.viewed_at) over viewed_pairs as viewed_at,
        first_value(vp.reaction) over viewed_pairs as reaction
    from bot_user bu
        join viewed_pair vp on bu.telegram_id = vp.recipient_telegram_id
    where bu.status = ?
    window viewed_pairs as (partition by vp.recipient_telegram_id order by viewed_at desc)
) as latest_recipients_views
where latest_recipients_views.viewed_at < ?::timestamptz - interval '12 hours' and latest_recipients_views.reaction is not null
qqqqqqqqqq
                ,
                [(new Registered())->value(), $this->now->value()],
                $this->connection
            ))
                ->response();
    }
}