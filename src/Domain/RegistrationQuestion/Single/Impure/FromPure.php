<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion as PureRegistrationQuestion;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure implements RegistrationQuestion
{
    private $pureRegistrationQuestion;
    private $cached;

    public function __construct(PureRegistrationQuestion $pureRegistrationQuestion)
    {
        $this->pureRegistrationQuestion = $pureRegistrationQuestion;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        return new Successful(new Present($this->pureRegistrationQuestion->value()));
    }
}