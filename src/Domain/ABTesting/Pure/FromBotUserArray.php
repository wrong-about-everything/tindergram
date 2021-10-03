<?php

declare(strict_types=1);

namespace TG\Domain\ABTesting\Pure;

use TG\Infrastructure\ABTesting\Pure\VariantId;

class FromBotUserArray extends VariantId
{
    private $botUser;
    private $concrete;

    public function __construct(array $botUser)
    {
        $this->botUser = $botUser;
        $this->concrete = null;
    }

    public function value(): int
    {
        return $this->concrete()->value();
    }

    public function exists(): bool
    {
        return $this->concrete()->exists();
    }

    private function concrete(): VariantId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): VariantId
    {
        return new FromInteger($this->botUser['variant_id']);
    }
}