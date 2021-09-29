<?php

declare(strict_types=1);

namespace TG\Domain\UserMode\Pure;

class FromBotUserDatabaseRecord extends Mode
{
    private $concrete;

    public function __construct(array $botUserDatabaseRecord)
    {
        $this->concrete = $this->concrete($botUserDatabaseRecord);
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(array $botUserDatabaseRecord): Mode
    {
        if (empty($botUserDatabaseRecord)) {
            return new NonExistent();
        }

        return new FromInteger($botUserDatabaseRecord['user_mode']);
    }
}