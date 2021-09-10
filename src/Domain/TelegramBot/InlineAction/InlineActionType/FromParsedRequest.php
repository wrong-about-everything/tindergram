<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\InlineAction\InlineActionType;

class FromParsedRequest extends InlineActionType
{
    private $concrete;

    public function __construct(array $parsedRequest)
    {
        $this->concrete = $this->concrete($parsedRequest);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(array $parsedRequest): InlineActionType
    {
        $parsedCallbackData = json_decode($parsedRequest['callback_query']['data'] ?? json_encode([]), true);
        if (!isset($parsedCallbackData['type']) || !is_int($parsedCallbackData['type'])) {
            return new NonExistent();
        }

        return new FromInteger($parsedCallbackData['type']);
    }
}