<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use TG\Domain\BotUser\WriteModel\IncrementedViewsQty;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class SentPair implements Pair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $pairName;
    private $httpTransport;
    private $connection;
    private $cached;

    public function __construct(
        InternalTelegramUserId $recipientTelegramId,
        InternalTelegramUserId $pairTelegramId,
        string $pairName,
        HttpTransport $httpTransport,
        OpenConnection $connection
    )
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->pairName = $pairName;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $sentPair = $this->sent();
        if (!$sentPair->value()->isSuccessful()) {
            return $sentPair->value();
        }

        $persistentPair = $this->persistent();
        if (!$persistentPair->value()->isSuccessful()) {
            return $persistentPair->value();
        }

        return (new IncrementedViewsQty($this->pairTelegramId, $this->connection))->value();
    }

    private function sent(): Pair
    {
        return
            new SentByHttp(
                $this->recipientTelegramId,
                $this->pairTelegramId,
                $this->pairName,
                $this->httpTransport
            );
    }

    private function persistent(): Pair
    {
        return new Persistent($this->recipientTelegramId, $this->pairTelegramId, $this->connection);
    }
}