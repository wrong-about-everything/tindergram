<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\InlineKeyboardButton\Multiple;

use TG\Infrastructure\TelegramBot\InlineKeyboardButton\Single\InlineKeyboardButton;

class LinedUpInARow implements InlineKeyboardButtons
{
    /**
     * @var InlineKeyboardButton[] $inlineButtonsArray
     */
    private $inlineButtonsArray;

    public function __construct(array $inlineButtonsArray)
    {
        $this->inlineButtonsArray = $inlineButtonsArray;
    }

    public function value(): array
    {
        return
            array_reduce(
                $this->inlineButtonsArray,
                function (array $inlineKeyboard, InlineKeyboardButton $currentInlineButton) {
                    $inlineKeyboard[0][] = $currentInlineButton->value();
                    return $inlineKeyboard;
                },
                []
            );
    }
}