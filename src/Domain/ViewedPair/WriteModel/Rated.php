<?php

declare(strict_types=1);

namespace TG\Domain\ViewedPair\WriteModel;

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

class Rated implements ViewedPair
{
    private $recipientTelegramId;
    private $pairTelegramId;
    private $action;
    private $connection;

    public function __construct(InternalTelegramUserId $recipientTelegramId, InternalTelegramUserId $pairTelegramId, InlineAction $action, OpenConnection $connection)
    {
        $this->recipientTelegramId = $recipientTelegramId;
        $this->pairTelegramId = $pairTelegramId;
        $this->action = $action;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $viewedPairResponse =
            (new SingleMutating(
                <<<query
insert into viewed_pair (recipient_telegram_id, pair_telegram_id, viewed_at, reaction)
values (?, ?, ?, ?)
query
                ,
                [$this->recipientTelegramId->value(), $this->pairTelegramId->value(), (new Now())->value(), (new FromAction($this->action))->value()],
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