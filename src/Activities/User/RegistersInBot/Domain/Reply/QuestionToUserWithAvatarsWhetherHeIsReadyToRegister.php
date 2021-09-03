<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class QuestionToUserWithAvatarsWhetherHeIsReadyToRegister implements MessageToUser
{
    public function value(): string
    {
        return
            <<<qqq
Вот фотографии, которые увидят другие пользователи.
Бот всегда берёт первые пять аватарок из telegram и показывает другим пользователям. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показывать. А если загрузите новую аватарку, бот её сразу же увидит и её будут видеть другие пользователи.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.
qqq
            ;
    }
}