<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction\InlineActionType;

class FromInteger extends InlineActionType
{
    private $concrete;

    public function __construct(int $typeId)
    {
        $this->concrete = $this->concrete($typeId);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(int $typeId): InlineActionType
    {
        return [
            (new Rating())->value() => new Rating(),
            (new TestCallbackType())->value() => new TestCallbackType(),
        ][$typeId] ?? new NonExistent();
    }
}