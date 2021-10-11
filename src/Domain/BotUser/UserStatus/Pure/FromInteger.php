<?php

declare(strict_types=1);

namespace TG\Domain\BotUser\UserStatus\Pure;

class FromInteger extends UserStatus
{
    private $concrete;

    public function __construct(int $status)
    {
        $this->concrete = isset($this->all()[$status]) ? $this->all()[$status] : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new RegistrationIsInProgress())->value() => new RegistrationIsInProgress(),
            (new InactiveBeforeRegistered())->value() => new InactiveBeforeRegistered(),
            (new Registered())->value() => new Registered(),
            (new InactiveAfterRegistered())->value() => new InactiveAfterRegistered(),
        ];
    }
}