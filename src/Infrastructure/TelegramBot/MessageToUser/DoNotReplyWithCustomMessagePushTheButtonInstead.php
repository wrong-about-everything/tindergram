<?php

declare(strict_types=1);

namespace TG\Infrastructure\TelegramBot\MessageToUser;

class DoNotReplyWithCustomMessagePushTheButtonInstead implements MessageToUser
{
    public function value(): string
    {
        return 'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @hey_sweetie_support_bot';
    }
}