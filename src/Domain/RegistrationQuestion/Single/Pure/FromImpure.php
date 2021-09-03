<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

use TG\Domain\RegistrationQuestion\Single\Impure\RegistrationQuestion as ImpureRegistrationQuestion;

class FromImpure implements RegistrationQuestion
{
    private $impureRegistrationQuestion;
    private $concrete;

    public function __construct(ImpureRegistrationQuestion $impureRegistrationQuestion)
    {
        $this->impureRegistrationQuestion = $impureRegistrationQuestion;
        $this->concrete = null;
    }

    public function id(): string
    {
        return $this->concrete()->id();
    }

    public function ordinalNumber(): int
    {
        return $this->concrete()->ordinalNumber();
    }

    public function exists(): bool
    {
        return $this->concrete()->exists();
    }

    private function concrete(): RegistrationQuestion
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): RegistrationQuestion
    {
        return new ById($this->impureRegistrationQuestion->id()->pure()->raw());
    }
}