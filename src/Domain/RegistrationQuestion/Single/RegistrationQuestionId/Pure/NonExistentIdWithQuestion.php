<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\RegistrationQuestionId\Pure;

use Exception;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion;

class NonExistentIdWithQuestion extends RegistrationQuestionId
{
    private $registrationQuestion;

    public function __construct(RegistrationQuestion $registrationQuestion)
    {
        $this->registrationQuestion = $registrationQuestion;
    }

    public function value(): string
    {
        throw new Exception(sprintf('Id of a registration question "%s" does not exist', $this->registrationQuestion->value()));
    }

    public function exists(): bool
    {
        return false;
    }
}