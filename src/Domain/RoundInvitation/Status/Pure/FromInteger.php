<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Pure;

class FromInteger extends Status
{
    private $concrete;

    public function __construct(int $status)
    {
        $this->concrete = $this->all()[$status] ?? new NonExistent();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    private function all()
    {
        return [
            (new _New())->value() => new _New(),
            (new Sent())->value() => new Sent(),
            (new ErrorDuringSending())->value() => new ErrorDuringSending(),
            (new Declined())->value() => new Declined(),
            (new Accepted())->value() => new Accepted(),
        ];
    }
}