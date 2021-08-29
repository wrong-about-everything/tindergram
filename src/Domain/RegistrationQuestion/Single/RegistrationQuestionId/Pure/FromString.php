<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;
use TG\Domain\RegistrationQuestion\Single\Pure\WhatDoYouPrefer;

class FromString extends RegistrationQuestionId
{
    private $concrete;

    public function __construct(string $id)
    {
        $this->concrete = $this->concrete($id);
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(string $id): RegistrationQuestionId
    {
        return [
            (new WhatDoYouPrefer())->value() => new WhatDoYouPreferId()
        ][$id->value()]
            ??
        new NonExistentIdWithQuestion($id);
    }
}