<?php

declare(strict_types=1);

namespace TG\Domain\TelegramBot\KeyboardButtons;

use TG\Domain\RegistrationAnswerOption\Multiple\Pure\RegistrationAnswerOptions;
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
        return
            array_reduce(
                $this->options->value(),
                function (array $keyboard, string $currentText) {
                    if (empty($keyboard) || $keyboard[count($keyboard) - 1] === 2) {
                        $keyboard[] = [['text' => $currentText]];
                    } else {
                        $keyboard[count($keyboard) - 1][] = ['text' => $currentText];
                    }

                    return $keyboard;
                },
                []
            );
    }
}