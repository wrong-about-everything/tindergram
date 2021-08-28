<?php

declare(strict_types=1);

namespace TG\Domain\SentReplyToUser;

use TG\Infrastructure\ImpureInteractions\ImpureValue;

/**
 * @deprecated
 * @todo: Все эти классы можно параметризировать: передавать текст сообщения, адресата и клавиатуру.
 * Поэтому вместо этого интерфейса надо использовать TG\Infrastructure\TelegramBot\SentReplyToUser\SentReplyToUser
 */
interface SentReplyToUser
{
    public function value(): ImpureValue;
}