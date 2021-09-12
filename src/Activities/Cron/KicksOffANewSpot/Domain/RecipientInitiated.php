<?php

declare(strict_types=1);

namespace TG\Activities\Cron\KicksOffANewSpot\Domain;

use TG\Domain\Pair\WriteModel\Pair;
use TG\Domain\Pair\WriteModel\SentPair;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class RecipientInitiated implements Pair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $firstName;
    private $transport;
    private $connection;

    public function __construct(
        InternalTelegramUserId $recipientTelegramId,
        InternalTelegramUserId $pairTelegramId,
        string $firstName,
        HttpTransport $transport,
        OpenConnection $connection
    )
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->firstName = $firstName;
        $this->transport = $transport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $sentPair = $this->sentPair();
        if (!$sentPair->value()->isSuccessful()) {
            return $sentPair->value();
        }

        return
            (new SingleMutating(
                'update bot_user set is_initiated = ? where telegram_id = ?',
                [1, $this->recipientTelegramId->value()],
                $this->connection
            ))
                ->response();
    }

    private function sentPair()
    {
        return
            new SentPair(
                $this->recipientTelegramId,
                $this->pairTelegramId,
                $this->firstName,
                $this->transport,
                $this->connection
            );
    }
}