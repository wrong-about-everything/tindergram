<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair\ReadModel;

use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class ByVoterTelegramIdAndRatedTelegramId implements ViewedPair
{
    private $voterTelegramId;
    private $ratedTelegramId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $voterTelegramId, InternalTelegramUserId $ratedTelegramId, OpenConnection $connection)
    {
        $this->voterTelegramId = $voterTelegramId;
        $this->ratedTelegramId = $ratedTelegramId;
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
        $response =
            (new Selecting(
                'select * from viewed_pair where recipient_telegram_id = ? and pair_telegram_id = ?',
                [$this->voterTelegramId->value(), $this->ratedTelegramId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}