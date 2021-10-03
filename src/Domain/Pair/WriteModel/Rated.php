<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use Exception;
use TG\Domain\Reaction\Pure\Dislike;
use TG\Domain\Reaction\Pure\FromAction;
use TG\Domain\Reaction\Pure\Like;
use TG\Domain\Reaction\Pure\Reaction;
use TG\Domain\TelegramBot\InlineAction\ThumbsDown;
use TG\Domain\TelegramBot\InlineAction\ThumbsUp;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Impure\InternalTelegramUserId;

class Rated implements Pair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $reaction;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, Reaction $reaction, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->reaction = $reaction;
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
        if (!$this->recipientTelegramId->exists()->isSuccessful() || $this->recipientTelegramId->exists()->pure()->raw() !== true) {
            return $this->recipientTelegramId->exists();
        }
        if (!$this->pairTelegramId->exists()->isSuccessful() || $this->pairTelegramId->exists()->pure()->raw() !== true) {
            return $this->pairTelegramId->exists();
        }
        $viewedPairResponse =
            (new SingleMutating(
                'update viewed_pair set reaction = ? where recipient_telegram_id = ? and pair_telegram_id = ?',
                [$this->reaction->value(), $this->recipientTelegramId->value()->pure()->raw(), $this->pairTelegramId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$viewedPairResponse->isSuccessful()) {
            return $viewedPairResponse;
        }

        if ($this->reaction->equals(new Like())) {
            return
                (new SingleMutating(
                    'update bot_user set like_qty = like_qty + 1 where telegram_id = ?',
                    [$this->pairTelegramId->value()->pure()->raw()],
                    $this->connection
                ))
                    ->response();
        } elseif ($this->reaction->equals(new Dislike())) {
            return
                (new SingleMutating(
                    'update bot_user set dislike_qty = dislike_qty + 1 where telegram_id = ?',
                    [$this->pairTelegramId->value()->pure()->raw()],
                    $this->connection
                ))
                    ->response();
        }

        throw new Exception(sprintf('Unknown reaction %s', $this->reaction->value()));
    }
}