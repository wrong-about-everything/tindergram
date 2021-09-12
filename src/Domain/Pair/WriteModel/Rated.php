<?php

declare(strict_types=1);

namespace TG\Domain\Pair\WriteModel;

use Exception;
use Meringue\Timeline\Point\Now;
use TG\Domain\Reaction\Pure\FromAction;
use TG\Domain\TelegramBot\InlineAction\InlineAction;
use TG\Domain\TelegramBot\InlineAction\ThumbsDown;
use TG\Domain\TelegramBot\InlineAction\ThumbsUp;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class Rated implements Pair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $action;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, InlineAction $action, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->action = $action;
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
        $viewedPairResponse =
            (new SingleMutating(
                'update viewed_pair set reaction = ? where recipient_telegram_id = ? and pair_telegram_id = ?',
                [(new FromAction($this->action))->value(), $this->recipientTelegramId->value(), $this->pairTelegramId->value(), ],
                $this->connection
            ))
                ->response();
        if (!$viewedPairResponse->isSuccessful()) {
            return $viewedPairResponse;
        }

        if ($this->action->equals(new ThumbsUp())) {
            return
                (new SingleMutating(
                    'update bot_user set like_qty = like_qty + 1 where telegram_id = ?',
                    [$this->pairTelegramId->value()],
                    $this->connection
                ))
                    ->response();
        } elseif ($this->action->equals(new ThumbsDown())) {
            return
                (new SingleMutating(
                    'update bot_user set dislike_qty = dislike_qty + 1 where telegram_id = ?',
                    [$this->pairTelegramId->value()],
                    $this->connection
                ))
                    ->response();
        }

        throw new Exception(sprintf('Unknown action %s', $this->action->value()));
    }
}