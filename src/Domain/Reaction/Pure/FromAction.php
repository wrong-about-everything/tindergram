<?php

declare(strict_types=1);

namespace TG\Domain\Reaction\Pure;

use Exception;
use TG\Domain\TelegramBot\InlineAction\InlineAction;
use TG\Domain\TelegramBot\InlineAction\ThumbsDown;
use TG\Domain\TelegramBot\InlineAction\ThumbsUp;

class FromAction extends Reaction
{
    private $concrete;

    public function __construct(InlineAction $action)
    {
        $this->concrete = $this->concrete($action);
    }

    function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(InlineAction $action): Reaction
    {
        if ($action->equals(new ThumbsUp())) {
            return new Like();
        } elseif ($action->equals(new ThumbsDown())) {
            return new Dislike();
        }

        throw new Exception(sprintf('Unknown action %s given', $action->value()));
    }
}