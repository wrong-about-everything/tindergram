<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

use Exception;

class MarkdownV2 implements MessageToUser
{
    private $message;

    public function __construct(string $message)
    {
        if ($message === '') {
            throw new Exception('Message can not be empty');
        }
        $this->message = $message;
    }

    public function value(): string
    {
        $split = preg_split('//u', $this->message, -1, PREG_SPLIT_NO_EMPTY);
        if (is_null($split)) {
            return $this->message;
        }

        return
            array_reduce(
                $split,
                function (string $carry, string $char) {
                    return
                        $carry
                            .
                        (
                            (in_array($char, ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!', '\\']))
                                ? sprintf('\%s', $char)
                                : $char
                        );
                },
                ''
            );
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}