<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction;

class FromInteger extends InlineAction
{
    private $concrete;

    public function __construct(int $actionId)
    {
        $this->concrete = $this->concrete($actionId);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $actionId): InlineAction
    {
        return [
            (new ThumbsDown())->value() => new ThumbsDown(),
            (new ThumbsUp())->value() => new ThumbsUp(),
        ][$actionId] ?? new NonExistent();
    }
}