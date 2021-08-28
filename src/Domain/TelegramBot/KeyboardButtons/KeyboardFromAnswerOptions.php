<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\KeyboardButtons;

use TG\Domain\RegistrationQuestionAnswer\RegistrationAnswerOptions\Pure\RegistrationAnswerOptions;
use TG\Infrastructure\TelegramBot\KeyboardButtons\KeyboardButtons;

class KeyboardFromAnswerOptions implements KeyboardButtons
{
    private $options;

    public function __construct(RegistrationAnswerOptions $options)
    {
        $this->options = $options;
    }

    public function value(): array
    {
        return [
            [['text' => $this->options->value()[0]], ['text' => $this->options->value()[1]]]
        ];
    }
}